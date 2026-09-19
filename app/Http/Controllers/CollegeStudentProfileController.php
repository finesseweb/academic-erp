<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Models\ReservationCategory;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentProfileValue;
use App\Services\StudentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CollegeStudentProfileController extends Controller
{
    public function index(Request $request, College $college): Response
    {
        abort_unless($request->user()->hasCollegePermission('college_student_profile.view', $college->id), 403);
        $sessions = AcademicSession::query()->where('university_id',$college->university_id)->where('status','ACTIVE')->orderByDesc('is_current')->orderByDesc('starts_on')->get(['id','name','code','is_current']);
        $sessionId=(int)$request->query('session_id',(int)($sessions->firstWhere('is_current',true)?->id ?? $sessions->first()?->id ?? 0));
        $offerings=CollegeProgramOffering::query()->with('programTemplate:id,name,code')->where('college_id',$college->id)->when($sessionId,fn($q)=>$q->where('academic_session_id',$sessionId))->orderBy('program_template_id')->get(['id','program_template_id','academic_session_id'])->map(fn($o)=>['id'=>(int)$o->id,'name'=>$o->programTemplate?->name,'code'=>$o->programTemplate?->code]);
        $offeringId=(int)$request->query('offering_id',0); if($offeringId&&!$offerings->contains('id',$offeringId))$offeringId=0;
        $q=trim((string)$request->query('q','')); $per=(int)$request->query('per_page',25); if(!in_array($per,[25,50,100],true))$per=25;
        $rows=StudentEnrollment::query()->with(['student:id,full_name,student_uid,university_roll_no,email,phone,status','offering.programTemplate:id,name,code','offering.academicSession:id,name,code','discipline:id,name,code'])
            ->where('college_id',$college->id)->where('status','ENROLLED')->when($sessionId,fn($x)=>$x->whereHas('offering',fn($o)=>$o->where('academic_session_id',$sessionId)))->when($offeringId,fn($x)=>$x->where('college_program_offering_id',$offeringId))
            ->when($q!=='',fn($x)=>$x->whereHas('student',fn($s)=>$s->where('full_name','like','%'.$q.'%')->orWhere('student_uid','like','%'.$q.'%')->orWhere('university_roll_no','like','%'.$q.'%')->orWhere('email','like','%'.$q.'%')->orWhere('phone','like','%'.$q.'%')))
            ->orderByDesc('id')->paginate($per)->withQueryString()->through(fn($e)=>['enrollment_id'=>(int)$e->id,'student_id'=>(int)$e->student_id,'name'=>$e->student?->full_name,'student_uid'=>$e->student?->student_uid,'university_roll_no'=>$e->student?->university_roll_no,'class_roll_no'=>$e->class_roll_no,'email'=>$e->student?->email,'phone'=>$e->student?->phone,'programme'=>$e->offering?->programTemplate?->name,'session'=>$e->offering?->academicSession?->name,'discipline'=>$e->discipline?->name,'source_type'=>$e->source_type]);
        return Inertia::render('college-student-profiles/index',['college'=>$college->only(['id','name','code']),'sessions'=>$sessions,'offerings'=>$offerings,'students'=>$rows,'filters'=>['session_id'=>$sessionId,'offering_id'=>$offeringId,'q'=>$q,'per_page'=>$per]]);
    }

    public function show(Request $request, College $college, Student $student, StudentImportService $profileFieldResolver): Response
    {
        abort_unless($request->user()->hasCollegePermission('college_student_profile.view',$college->id),403); abort_unless((int)$student->college_id===(int)$college->id,404);
        $student->load(['profileValues.sourceField.options','enrollments'=>fn($q)=>$q->with(['offering.programTemplate:id,name,code','offering.academicSession:id,name,code','discipline:id,name,code'])->orderByDesc('enrolled_at')]);
        $enrollmentIds=$student->enrollments->pluck('id');
        $courseRows=DB::table('student_enrollment_course_choices as ec')
            ->join('curriculum_course_mappings as mapping','mapping.id','=','ec.curriculum_course_mapping_id')
            ->join('curriculum_slots as slot','slot.id','=','mapping.curriculum_slot_id')
            ->leftJoin('course_categories as category','category.id','=','slot.course_category_id')
            ->leftJoin('academic_disciplines as source_discipline','source_discipline.id','=','mapping.source_discipline_id')
            ->join('courses as c','c.id','=','ec.course_id')
            ->whereIn('ec.student_enrollment_id',$enrollmentIds)
            ->where('ec.selection_source','APPLICANT_CHOICE')
            ->orderBy('ec.student_enrollment_id')->orderBy('slot.display_order')->orderBy('ec.id')
            ->get(['ec.student_enrollment_id','category.name as category_name','source_discipline.name as source_name','c.name as course_name']);
        $currentEnrollment=$student->enrollments->first();
        $configuredPhotoField=$currentEnrollment?->offering
            ? $profileFieldResolver->profileFieldsForOffering($college,$currentEnrollment->offering,true)
                ->first(fn($field)=>$field->system_purpose==='CANDIDATE_PROFILE_PHOTO' && $field->field_type==='IMAGE')
            : null;
        $profiles=$student->profileValues->map(function($v) use ($college){
            $f=$v->sourceField;
            $options=$f?->options?->where('is_active',true)->sortBy('display_order')->values()->map(fn($o)=>['value'=>$o->value,'label'=>$o->label])->all()??[];
            // Keep Student Profile reservation-category semantics identical to the authoritative Admission Form runtime:
            // GENERAL is the canonical open/unreserved value; university master supplies only other ACTIVE VERTICAL categories.
            if ($f?->system_purpose === 'CANDIDATE_RESERVATION_CATEGORY') {
                $options=collect([['value'=>'GENERAL','label'=>'General / Unreserved']])->concat(
                    ReservationCategory::query()
                        ->where('university_id',$college->university_id)
                        ->where('status','ACTIVE')
                        ->where('nature','VERTICAL')
                        ->whereRaw("LOWER(TRIM(code)) NOT IN ('general','gen','open','unreserved','ur')")
                        ->orderBy('display_order')->orderBy('name')->get(['code','name'])
                        ->map(fn($c)=>['value'=>$c->code,'label'=>$c->name.' · '.$c->code])
                )->values()->all();
            }
            return ['id'=>(int)$v->id,'key'=>$v->profile_key,'label'=>$v->label_snapshot,'field_type'=>$f?->field_type??'TEXT','system_purpose'=>$f?->system_purpose,'required'=>(bool)($f?->is_required??false),'value_text'=>$v->value_text,'value_json'=>$v->value_json,'file_name'=>$v->file_name,'file_url'=>$v->file_path ? route('college-student-profiles.profile-file',[$college->id,$v->student_id,$v->id]).'?v='.rawurlencode((string)($v->updated_at?->getTimestamp() ?? 0)) : null,'options'=>$options];
        })->values();
        if($configuredPhotoField && ! $profiles->contains(fn($profile)=>$profile['system_purpose']==='CANDIDATE_PROFILE_PHOTO')){
            $profiles->push([
                'id'=>null,
                'key'=>$configuredPhotoField->student_profile_key,
                'label'=>$configuredPhotoField->label,
                'field_type'=>$configuredPhotoField->field_type,
                'system_purpose'=>$configuredPhotoField->system_purpose,
                'required'=>(bool)$configuredPhotoField->is_required,
                'value_text'=>null,
                'value_json'=>null,
                'file_name'=>null,
                'file_url'=>null,
                'options'=>[],
            ]);
        }
        $curricula=DB::table('curricula')->whereIn('id',$student->enrollments->pluck('curriculum_id')->filter()->unique())->get(['id','name','code','version'])->keyBy('id');
        $enrollments=$student->enrollments->map(function($e) use ($courseRows,$curricula){
            $curriculum=$e->curriculum_id ? $curricula->get($e->curriculum_id) : null;
            $academicChoices=$courseRows->where('student_enrollment_id',$e->id)->groupBy(fn($row)=>(string)($row->category_name ?: 'Choice / Elective'))->map(function($rows,$category){
                $sourceNames=$rows->pluck('source_name')->filter()->unique()->values();
                $values=$sourceNames->isNotEmpty() ? $sourceNames : $rows->pluck('course_name')->filter()->unique()->values();
                return ['category'=>$category,'values'=>$values->all()];
            })->values();
            return ['id'=>(int)$e->id,'session'=>$e->offering?->academicSession?->name,'programme'=>$e->offering?->programTemplate?->name,'discipline'=>$e->discipline?->name,'curriculum'=>$curriculum ? ['name'=>$curriculum->name,'code'=>$curriculum->code,'version'=>$curriculum->version] : null,'specialization_id'=>$e->specialization_id,'class_roll_no'=>$e->class_roll_no,'status'=>$e->status,'source_type'=>$e->source_type,'academic_choices'=>$academicChoices];
        })->values();
        return Inertia::render('college-student-profiles/show',['college'=>$college->only(['id','name','code']),'student'=>array_merge($student->only(['id','full_name','email','phone','student_uid','university_roll_no','status','source_type']),['date_of_birth'=>$student->date_of_birth?->format('Y-m-d')]),'profiles'=>$profiles,'enrollments'=>$enrollments,'can'=>['edit'=>$request->user()->hasCollegePermission('college_student_profile.edit',$college->id)]]);
    }

    public function profileFile(Request $request, College $college, Student $student, StudentProfileValue $profileValue)
    {
        abort_unless($request->user()->hasCollegePermission('college_student_profile.view',$college->id),403);
        abort_unless((int)$student->college_id===(int)$college->id && (int)$profileValue->student_id===(int)$student->id,404);
        abort_unless(filled($profileValue->file_path) && Storage::disk('local')->exists($profileValue->file_path),404);
        return Storage::disk('local')->response($profileValue->file_path,$profileValue->file_name ?: basename($profileValue->file_path),[
            'Content-Type'=>$profileValue->file_mime ?: 'application/octet-stream',
            'Content-Disposition'=>'inline; filename="'.addslashes($profileValue->file_name ?: basename($profileValue->file_path)).'"',
        ]);
    }

    public function updateProfilePhoto(Request $request, College $college, Student $student, StudentImportService $profileFieldResolver): RedirectResponse
    {
        abort_unless($request->user()->hasCollegePermission('college_student_profile.edit',$college->id),403);
        abort_unless((int)$student->college_id===(int)$college->id,404);
        $photoValue=$student->profileValues()->with('sourceField')->get()->first(fn($v)=>$v->sourceField?->student_data_policy==='STUDENT_PROFILE' && $v->sourceField?->system_purpose==='CANDIDATE_PROFILE_PHOTO');
        $photoField=$photoValue?->sourceField;
        if(! $photoField){
            $currentEnrollment=$student->enrollments()->with('offering.programTemplate.degree')->where('status','ENROLLED')->orderByDesc('enrolled_at')->orderByDesc('id')->first();
            $photoField=$currentEnrollment?->offering
                ? $profileFieldResolver->profileFieldsForOffering($college,$currentEnrollment->offering,true)
                    ->first(fn($field)=>$field->system_purpose==='CANDIDATE_PROFILE_PHOTO' && $field->field_type==='IMAGE')
                : null;
        }
        abort_unless($photoField,404);
        $rules=$photoField->validation_rules ?? [];
        $maxKb=(int)($rules['max_kb'] ?? 5120);
        $extensions=$rules['extensions'] ?? ['jpg','jpeg','png','webp'];
        if(is_string($extensions))$extensions=array_values(array_filter(array_map('trim',explode(',',$extensions))));
        $data=$request->validate(['profile_photo'=>['required','image','max:'.$maxKb,'mimes:'.implode(',',$extensions ?: ['jpg','jpeg','png','webp'])]]);
        /** @var UploadedFile $file */ $file=$data['profile_photo'];
        $oldPath=$photoValue?->file_path;
        $path=$file->store("student-profiles/{$college->id}/{$student->id}",'local');
        $before=['file_name'=>$photoValue?->file_name,'file_mime'=>$photoValue?->file_mime,'file_size'=>$photoValue?->file_size];
        $photoValue=StudentProfileValue::query()->updateOrCreate(
            ['student_id'=>$student->id,'profile_key'=>$photoField->student_profile_key],
            ['source_application_field_id'=>$photoField->id,'label_snapshot'=>$photoField->label,'file_path'=>$path,'file_name'=>$file->getClientOriginalName(),'file_mime'=>$file->getClientMimeType(),'file_size'=>$file->getSize(),'value_text'=>null,'value_json'=>null]
        );
        if($oldPath && str_starts_with($oldPath,'student-profiles/') && $oldPath!==$path)Storage::disk('local')->delete($oldPath);
        DB::table('audit_logs')->insert(['actor_user_id'=>$request->user()->id,'event'=>'student.profile.photo.updated','resource_type'=>'student','resource_id'=>$student->id,'scope_type'=>'COLLEGE','scope_reference'=>'college:'.$college->id,'before'=>json_encode($before),'after'=>json_encode(['file_name'=>$photoValue->file_name,'file_mime'=>$photoValue->file_mime,'file_size'=>$photoValue->file_size]),'ip_address'=>$request->ip(),'created_at'=>now()]);
        return back()->with('toast',['type'=>'success','message'=>'Student profile photo updated successfully.']);
    }

    public function update(Request $request, College $college, Student $student): RedirectResponse
    {
        abort_unless($request->user()->hasCollegePermission('college_student_profile.edit',$college->id),403); abort_unless((int)$student->college_id===(int)$college->id,404);
        $data=$request->validate(['full_name'=>['required','string','max:180'],'date_of_birth'=>['nullable','date'],'email'=>['nullable','email','max:190'],'phone'=>['nullable','string','max:40'],'profile_values'=>['nullable','array'],'profile_values.*'=>['nullable']]);
        $editableValues=$student->profileValues()->with('sourceField')->get();
        foreach($editableValues as $value){$field=$value->sourceField;if(!$field||$field->student_data_policy!=='STUDENT_PROFILE'||in_array($field->field_type,['FILE','IMAGE'],true))continue;$raw=($data['profile_values']??[])[$value->profile_key]??null;if($raw==='__blank')$raw=null;if($field->is_required && ($raw===null||$raw===''||(is_array($raw)&&count($raw)===0)))throw ValidationException::withMessages(['profile_values.'.$value->profile_key=>$value->label_snapshot.' is required.']);}
        $before=['student'=>$student->only(['full_name','date_of_birth','email','phone']),'profile_values'=>$student->profileValues()->get()->mapWithKeys(fn($v)=>[$v->profile_key=>$v->value_json??$v->value_text])->all()];
        DB::transaction(function()use($request,$student,$data){$student->update(['full_name'=>trim($data['full_name']),'date_of_birth'=>$data['date_of_birth']??null,'email'=>$data['email']??null,'phone'=>$data['phone']??null,'updated_by'=>$request->user()->id]);
            $submitted=$data['profile_values']??[]; $values=$student->profileValues()->with('sourceField')->get(); foreach($values as $value){$field=$value->sourceField; if(!$field||$field->student_data_policy!=='STUDENT_PROFILE')continue; $raw=$submitted[$value->profile_key]??null; if($raw==='__blank')$raw=null; if(in_array($field->field_type,['FILE','IMAGE'],true))continue; if(in_array($field->field_type,['MULTISELECT','CHECKBOX'],true)){$value->value_json=is_array($raw)?array_values($raw):[];$value->value_text=null;}else{$value->value_text=is_scalar($raw)?trim((string)$raw):null;$value->value_json=null;} $value->save();}
        });
        $after=['student'=>$student->fresh()->only(['full_name','date_of_birth','email','phone']),'profile_values'=>$student->profileValues()->get()->mapWithKeys(fn($v)=>[$v->profile_key=>$v->value_json??$v->value_text])->all()];
        if($before!==$after)DB::table('audit_logs')->insert(['actor_user_id'=>$request->user()->id,'event'=>'student.profile.updated','resource_type'=>'student','resource_id'=>$student->id,'scope_type'=>'COLLEGE','scope_reference'=>'college:'.$college->id,'before'=>json_encode($before),'after'=>json_encode($after),'ip_address'=>$request->ip(),'created_at'=>now()]);
        return back()->with('toast',['type'=>'success','message'=>'Student profile updated successfully. Academic context and institutional identities were not changed.']);
    }
}
