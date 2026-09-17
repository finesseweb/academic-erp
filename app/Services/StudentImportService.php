<?php

namespace App\Services;

use App\Models\CollegeAdmissionCycle;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Models\CollegeAdmissionFormField;
use App\Models\CollegeAdmissionFormMapping;
use App\Models\CollegeAdmissionFormTemplate;
use App\Models\StudentProfileValue;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentIdentitySetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentImportService
{
    public const TARGETS=['full_name','date_of_birth','email','phone','student_uid','university_roll_no','class_roll_no','discipline_code','specialization_code'];

    public function __construct(private readonly ApplicantAcademicPreferenceService $academicPreferences) {}


    public function targetsForOffering(College $college, int $sessionId, int $offeringId): array
    {
        $targets = [
            ['key'=>'full_name','label'=>'Full Name','required'=>true,'group'=>'Core Student'],
            ['key'=>'date_of_birth','label'=>'Date of Birth (YYYY-MM-DD)','group'=>'Core Student'],
            ['key'=>'email','label'=>'Email','group'=>'Core Student'],
            ['key'=>'phone','label'=>'Phone','group'=>'Core Student'],
            ['key'=>'student_uid','label'=>'Existing Student UID','group'=>'Student Identity'],
            ['key'=>'university_roll_no','label'=>'Existing University Roll No.','group'=>'Student Identity'],
            ['key'=>'class_roll_no','label'=>'Existing Class Roll No.','group'=>'Student Identity'],
                    ];
        if (!$sessionId || !$offeringId) return $targets;
        $offering = $this->offering($college,$sessionId,$offeringId);
        foreach ($this->academicSchema($offering)['targets'] as $target) $targets[] = $target;
        foreach ($this->profileFieldsForOffering($college,$offering) as $field) {
            $targets[] = ['key'=>'profile:'.$field->id,'label'=>$field->label,'group'=>'Student Profile','profile_key'=>$field->student_profile_key,'field_type'=>$field->field_type,'required'=>(bool)$field->is_required];
        }
        return $targets;
    }

    public function importContextForOffering(College $college, int $sessionId, int $offeringId): array
    {
        if (! $sessionId || ! $offeringId) return ['template_found'=>null,'profile_field_count'=>0,'required_profile_field_count'=>0,'curriculum_found'=>null];
        $offering=$this->offering($college,$sessionId,$offeringId);
        $fields=$this->profileFieldsForOffering($college,$offering);
        return [
            'template_found'=>$this->hasApplicableProfileTemplate($college,$offering),
            'profile_field_count'=>$fields->count(),
            'required_profile_field_count'=>$fields->where('is_required',true)->count(),
            'curriculum_found'=>(bool)$offering->curriculum_id,
        ];
    }

    private function hasApplicableProfileTemplate(College $college, CollegeProgramOffering $offering): bool
    {
        return $this->applicableTemplateIds($college,$offering)->isNotEmpty();
    }

    private function applicableTemplateIds(College $college, CollegeProgramOffering $offering)
    {
        $offering->loadMissing('programTemplate.degree');
        $program=$offering->programTemplate; $degree=$program?->degree;
        $cycleIds=DB::table('college_admission_cycles')->where('college_program_offering_id',$offering->id)->pluck('id');
        return CollegeAdmissionFormMapping::query()->where('university_id',$college->university_id)->where('status','ACTIVE')
            ->where(fn($q)=>$q->whereNull('college_id')->orWhere('college_id',$college->id))
            ->where(fn($q)=>$q->whereNull('degree_level_id')->orWhere('degree_level_id',$degree?->degree_level_id))
            ->where(fn($q)=>$q->whereNull('degree_id')->orWhere('degree_id',$program?->degree_id))
            ->where(fn($q)=>$q->whereNull('program_template_id')->orWhere('program_template_id',$program?->id))
            ->where(fn($q)=>$q->whereNull('college_program_offering_id')->orWhere('college_program_offering_id',$offering->id))
            ->where(fn($q)=>$q->whereNull('college_admission_cycle_id')->when($cycleIds->isNotEmpty(),fn($x)=>$x->orWhereIn('college_admission_cycle_id',$cycleIds)))
            ->whereHas('template',fn($q)=>$q->where('status','ACTIVE'))
            ->pluck('college_admission_form_template_id')->unique()->values();
    }

    private function profileFieldsForOffering(College $college, CollegeProgramOffering $offering)
    {
        $offering->loadMissing('programTemplate.degree');
        $program=$offering->programTemplate; $degree=$program?->degree;
        $cycleIds=DB::table('college_admission_cycles')->where('college_program_offering_id',$offering->id)->pluck('id');
        $templateIds=$this->applicableTemplateIds($college,$offering);
        $parents=CollegeAdmissionFormTemplate::query()->whereIn('id',$templateIds)->whereNotNull('parent_template_id')->pluck('parent_template_id');
        $allTemplateIds=$templateIds->concat($parents)->unique()->values();
        if($allTemplateIds->isEmpty()) return collect();
        $fields=CollegeAdmissionFormField::query()->with('scopes')->where('status','ACTIVE')->where('student_data_policy','STUDENT_PROFILE')->whereNotNull('student_profile_key')
            ->whereNotIn('field_type',['FILE','IMAGE'])->whereHas('step',fn($q)=>$q->whereIn('college_admission_form_template_id',$allTemplateIds))->orderBy('display_order')->orderBy('id')->get();
        $context=['degree_level_id'=>$degree?->degree_level_id,'degree_id'=>$program?->degree_id,'program_template_id'=>$program?->id,'college_program_offering_id'=>$offering->id,'curriculum_id'=>$offering->curriculum_id];
        return $fields->filter(function($field) use($context,$cycleIds){
            $scopes=$field->scopes->where('is_active',true); if($scopes->isEmpty()) return true;
            return $scopes->contains(function($scope) use($context,$cycleIds){
                foreach($context as $key=>$value) if(filled($scope->{$key}) && (int)$scope->{$key}!==(int)$value) return false;
                if(filled($scope->college_admission_cycle_id) && !$cycleIds->contains((int)$scope->college_admission_cycle_id)) return false;
                return true;
            });
        })->unique('student_profile_key')->values();
    }

    private function academicSchema(CollegeProgramOffering $offering): array
    {
        $offering->loadMissing(['curriculum','programTemplate']);
        $cycle = new CollegeAdmissionCycle();
        $cycle->college_program_offering_id = $offering->id;
        $cycle->college_id = $offering->college_id;
        $cycle->setRelation('programOffering', $offering);
        $schema = $this->academicPreferences->importSchema($cycle);
        $schema['cycle'] = $cycle;
        return $schema;
    }

    private function dir(College $college,int $userId): string { return "student-imports/{$college->id}/{$userId}"; }
    private function csvPath(College $college,int $userId,string $token): string { return $this->dir($college,$userId)."/{$token}.csv"; }
    private function metaPath(College $college,int $userId,string $token): string { return $this->dir($college,$userId)."/{$token}.json"; }
    private function assertToken(string $token): void { if(!preg_match('/^[A-Za-z0-9]{40}$/',$token)) abort(404); }

    public function upload(College $college,int $userId,UploadedFile $file): string
    {
        $token=Str::random(40); $path=$this->csvPath($college,$userId,$token);
        Storage::disk('local')->put($path,file_get_contents($file->getRealPath()));
        return $token;
    }

    public function state(College $college,int $userId,?string $token): ?array
    {
        if(!$token) return null; $this->assertToken($token); $path=$this->csvPath($college,$userId,$token);
        if(!Storage::disk('local')->exists($path)) return null;
        $headers=$this->headers($path); $meta=null;
        $mp=$this->metaPath($college,$userId,$token);
        if(Storage::disk('local')->exists($mp)) $meta=json_decode(Storage::disk('local')->get($mp),true);
        return ['token'=>$token,'headers'=>$headers,'meta'=>$meta];
    }

    public function validateMapping(College $college,int $sessionId,int $offeringId,array $rawMapping): array
    {
        $mapping=array_filter($rawMapping,fn($v)=>is_string($v)&&$v!=='');
        if(empty($mapping['full_name'])) throw ValidationException::withMessages(['mapping.full_name'=>'Full Name must be mapped.']);
        $offering=$this->offering($college,$sessionId,$offeringId);
        $targets=collect($this->targetsForOffering($college,$sessionId,(int)$offering->id));
        $allowedTargets=$targets->pluck('key')->all();
        foreach(array_keys($mapping) as $target) if(!in_array($target,$allowedTargets,true)) throw ValidationException::withMessages(['mapping'=>'Invalid Student field mapping for this Programme Offering.']);
        $this->assertRequiredTargetsMapped($targets,$mapping);
        if(count($mapping)!==count(array_unique(array_values($mapping)))) throw ValidationException::withMessages(['mapping'=>'Each CSV column can be mapped only once.']);
        return $mapping;
    }

    private function assertRequiredTargetsMapped($targets,array $mapping): void
    {
        $missing=$targets->filter(fn($target)=>!empty($target['required']) && empty($mapping[$target['key']]))->pluck('label')->values();
        if($missing->isNotEmpty()) throw ValidationException::withMessages(['mapping'=>'Required fields must be mapped before validation: '.$missing->implode(', ').'.']);
    }

    public function preview(College $college,int $userId,string $token,array $data): array
    {
        $this->assertToken($token); $path=$this->csvPath($college,$userId,$token); if(!Storage::disk('local')->exists($path)) abort(404);
        $headers=$this->headers($path); $mapping=array_filter($data['mapping']??[],fn($v)=>is_string($v)&&$v!=='');
        if(empty($mapping['full_name'])) throw ValidationException::withMessages(['mapping.full_name'=>'Full Name must be mapped.']);
        $offering=$this->offering($college,(int)$data['session_id'],(int)$data['offering_id']);
        $targets=collect($this->targetsForOffering($college,(int)$data['session_id'],(int)$offering->id));
        $allowedTargets=$targets->pluck('key')->all();
        foreach($mapping as $target=>$header) if(!in_array($target,$allowedTargets,true)||!in_array($header,$headers,true)) throw ValidationException::withMessages(['mapping'=>'Invalid or unavailable CSV column mapping for this Programme Offering.']);
        $this->assertRequiredTargetsMapped($targets,$mapping);
        if(count($mapping)!==count(array_unique(array_values($mapping)))) throw ValidationException::withMessages(['mapping'=>'Each CSV column can be mapped only once.']);
        [$rows,$total,$valid,$invalid]=$this->scan($college,$path,$headers,$mapping,$offering,20);
        $meta=['session_id'=>(int)$data['session_id'],'offering_id'=>(int)$offering->id,'mapping'=>$mapping,'preview'=>$rows,'total'=>$total,'valid'=>$valid,'invalid'=>$invalid,'validated_at'=>now()->toIso8601String()];
        Storage::disk('local')->put($this->metaPath($college,$userId,$token),json_encode($meta));
        return $meta;
    }

    public function import(College $college,int $userId,string $token,int $actorId,?string $ip): int
    {
        $this->assertToken($token); $path=$this->csvPath($college,$userId,$token); $mp=$this->metaPath($college,$userId,$token);
        if(!Storage::disk('local')->exists($path)||!Storage::disk('local')->exists($mp)) throw ValidationException::withMessages(['import'=>'Upload and validate the CSV before importing.']);
        $meta=json_decode(Storage::disk('local')->get($mp),true); $headers=$this->headers($path); $offering=$this->offering($college,(int)$meta['session_id'],(int)$meta['offering_id']);
        [, $total,$valid,$invalid,$allRows]=$this->scan($college,$path,$headers,$meta['mapping'],$offering,0,true);
        if($invalid>0) throw ValidationException::withMessages(['import'=>"Import blocked: {$invalid} row(s) contain validation errors. Validate the file again."]);
        $count=DB::transaction(function() use($allRows,$college,$offering,$actorId,$ip,$total){
            $created=0;
            foreach($allRows as $row){
                $v=$row['values'];
                $student=Student::create(['college_id'=>$college->id,'user_id'=>null,'admission_id'=>null,'college_admission_application_id'=>null,'source_type'=>'IMPORT','student_uid'=>$v['student_uid']?:null,'university_roll_no'=>$v['university_roll_no']?:null,'full_name'=>$v['full_name'],'date_of_birth'=>$v['date_of_birth']?:null,'email'=>$v['email']?:null,'phone'=>$v['phone']?:null,'status'=>'ACTIVE','created_by'=>$actorId,'updated_by'=>$actorId]);
                $academic=$row['academic'];
                $enrollment=StudentEnrollment::create(['student_id'=>$student->id,'college_id'=>$college->id,'college_program_offering_id'=>$offering->id,'curriculum_id'=>$academic['curriculum_id']?:null,'discipline_id'=>$academic['discipline_id'],'specialization_id'=>$academic['specialization_id'],'admission_id'=>null,'class_roll_no'=>$v['class_roll_no']?:null,'class_roll_scope_key'=>$row['class_roll_scope_key'],'source_type'=>'IMPORT','status'=>'ENROLLED','enrolled_at'=>now(),'enrolled_by'=>$actorId]);
                foreach($academic['courses'] as $course) DB::table('student_enrollment_course_choices')->insert(['student_enrollment_id'=>$enrollment->id,'curriculum_term_id'=>$course['curriculum_term_id'],'curriculum_slot_id'=>$course['curriculum_slot_id'],'curriculum_course_mapping_id'=>$course['curriculum_course_mapping_id'],'course_id'=>$course['course_id'],'selection_source'=>$course['selection_source'],'created_at'=>now(),'updated_at'=>now()]);
                foreach($row['profile_values'] as $profile){
                    StudentProfileValue::create(['student_id'=>$student->id,'source_application_field_id'=>$profile['field_id'],'profile_key'=>$profile['profile_key'],'label_snapshot'=>$profile['label'],'value_text'=>$profile['value_text'],'value_json'=>$profile['value_json']]);
                }
                $created++;
            }
            DB::table('audit_logs')->insert(['actor_user_id'=>$actorId,'event'=>'student.import.completed','resource_type'=>'student_import','resource_id'=>null,'scope_type'=>'COLLEGE','scope_reference'=>'college:'.$college->id,'before'=>null,'after'=>json_encode(['programme_offering_id'=>$offering->id,'rows_imported'=>$created,'source_type'=>'IMPORT']),'ip_address'=>$ip,'created_at'=>now()]);
            return $created;
        });
        Storage::disk('local')->delete([$path,$this->metaPath($college,$userId,$token)]);
        return $count;
    }

    private function offering(College $college,int $sessionId,int $offeringId): CollegeProgramOffering
    {
        $o=CollegeProgramOffering::query()->whereKey($offeringId)->where('college_id',$college->id)->where('academic_session_id',$sessionId)->first();
        if(!$o) throw ValidationException::withMessages(['offering_id'=>'Select a valid Programme Offering for the selected Session.']); return $o;
    }

    private function headers(string $path): array
    {
        $h=fopen(Storage::disk('local')->path($path),'r'); $row=fgetcsv($h); fclose($h); if(!$row) throw ValidationException::withMessages(['file'=>'CSV header row is missing.']);
        $row=array_map(fn($v)=>trim(preg_replace('/^\xEF\xBB\xBF/','',(string)$v)),$row);
        if(count($row)!==count(array_unique($row))) throw ValidationException::withMessages(['file'=>'CSV contains duplicate column headers.']); return $row;
    }

    private function scan(College $college,string $path,array $headers,array $mapping,CollegeProgramOffering $offering,int $previewLimit=20,bool $returnAll=false): array
    {
        $fh=fopen(Storage::disk('local')->path($path),'r'); fgetcsv($fh); $preview=[];$all=[];$total=0;$valid=0;$invalid=0;$seen=[];
        $academicSchema=$this->academicSchema($offering);
        $identitySettings=StudentIdentitySetting::forCollege((int)$college->id);
        $existingStudentUids=Student::query()->where('college_id',$college->id)->whereNotNull('student_uid')->pluck('student_uid')->map(fn($v)=>strtoupper((string)$v))->flip();
        $existingUniversityRolls=Student::query()->where('college_id',$college->id)->whereNotNull('university_roll_no')->pluck('university_roll_no')->map(fn($v)=>strtoupper((string)$v))->flip();
        $existingClassRolls=StudentEnrollment::query()->where('college_id',$college->id)->whereNotNull('class_roll_no')->get(['class_roll_scope_key','class_roll_no'])->mapWithKeys(fn($e)=>[(string)$e->class_roll_scope_key.'|'.strtoupper((string)$e->class_roll_no)=>true]);
        $profileFields=$this->profileFieldsForOffering($college,$offering)->keyBy(fn($f)=>'profile:'.$f->id);
        while(($raw=fgetcsv($fh))!==false){ if(count(array_filter($raw,fn($x)=>trim((string)$x)!==''))===0) continue; $total++; $assoc=[]; foreach($headers as $i=>$h)$assoc[$h]=trim((string)($raw[$i]??'')); $v=[]; foreach(self::TARGETS as $t)$v[$t]=isset($mapping[$t])?($assoc[$mapping[$t]]??''):''; foreach($academicSchema['targets'] as $t)$v[$t['key']]=isset($mapping[$t['key']])?($assoc[$mapping[$t['key']]]??''):''; $errors=[];
            if($v['full_name']===''||mb_strlen($v['full_name'])>180)$errors[]='Full Name is required (max 180 characters).';
            if($v['date_of_birth']!==''&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$v['date_of_birth']))$errors[]='Date of Birth must use YYYY-MM-DD.';
            if($v['email']!==''&&!filter_var($v['email'],FILTER_VALIDATE_EMAIL))$errors[]='Email is invalid.';
            $academic=['curriculum_id'=>null,'discipline_id'=>null,'specialization_id'=>null,'courses'=>[]];
            try { $academic=$this->academicPreferences->resolveImportValues($academicSchema['cycle'],$v); } catch (ValidationException $e) { foreach($e->errors() as $messages) foreach($messages as $message) $errors[]=$message; }
            $disciplineId=$academic['discipline_id']??null;
            foreach(['student_uid','university_roll_no'] as $k){ if($v[$k]!==''){ $key=$k.'|'.strtoupper($v[$k]); if(isset($seen[$key]))$errors[]=ucwords(str_replace('_',' ',$k)).' is duplicated in this CSV.'; $seen[$key]=true; }}
            if($v['student_uid']!==''&&isset($existingStudentUids[strtoupper($v['student_uid'])]))$errors[]='Student UID already exists.';
            if($v['university_roll_no']!==''&&isset($existingUniversityRolls[strtoupper($v['university_roll_no'])]))$errors[]='University Roll No. already exists.';
            if($v['class_roll_no']!=='' && $identitySettings->class_roll_scope==='DISCIPLINE' && !$disciplineId)$errors[]='Discipline Code is required when importing an existing Class Roll under Discipline scope.';
            $classScopeKey=$identitySettings->class_roll_scope==='DISCIPLINE'&&$disciplineId?'OFFERING:'.$offering->id.'|DISCIPLINE:'.$disciplineId:'OFFERING:'.$offering->id;
            if($v['class_roll_no']!==''){ $csvClassKey='class_roll_no|'.$classScopeKey.'|'.strtoupper($v['class_roll_no']); if(isset($seen[$csvClassKey]))$errors[]='Class Roll No. is duplicated in the same configured scope in this CSV.'; $seen[$csvClassKey]=true; }
            if($v['class_roll_no']!==''&&isset($existingClassRolls[$classScopeKey.'|'.strtoupper($v['class_roll_no'])]))$errors[]='Class Roll No. already exists in its configured scope.';
            $profileValues=[];
            foreach($profileFields as $target=>$field){
                $header=$mapping[$target]??null;
                $rawValue=$header?trim((string)($assoc[$header]??'')):'';
                if($rawValue===''){ if($field->is_required)$errors[]=$field->label.' is required.'; continue; }
                $valueJson=null; $valueText=$rawValue;
                if(in_array($field->field_type,['CHECKBOX','MULTISELECT'],true)){ $parts=array_values(array_filter(array_map('trim',preg_split('/[|;]/',$rawValue)?:[]),fn($x)=>$x!=='')); $valueJson=$parts; $valueText=null; }
                $profileValues[]=['field_id'=>(int)$field->id,'profile_key'=>$field->student_profile_key,'label'=>$field->label,'value_text'=>$valueText,'value_json'=>$valueJson];
            }
            $row=['row'=>$total+1,'values'=>$v,'profile_values'=>$profileValues,'discipline_id'=>$disciplineId,'academic'=>$academic,'class_roll_scope_key'=>$v['class_roll_no']!==''?$classScopeKey:null,'errors'=>$errors]; if($errors)$invalid++;else$valid++; if($previewLimit===0||count($preview)<$previewLimit)$preview[]=$row; if($returnAll)$all[]=$row;
        } fclose($fh); return [$preview,$total,$valid,$invalid,$all];
    }
}
