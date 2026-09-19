<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Models\StudentImportMapping;
use App\Services\StudentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class CollegeStudentImportController extends Controller
{
    public function index(Request $request, College $college, StudentImportService $service): Response
    {
        abort_unless($request->user()->hasCollegePermission('college_student_import.view',$college->id),403);
        $sessions=AcademicSession::query()->where('university_id',$college->university_id)->where('status','ACTIVE')->orderByDesc('is_current')->orderByDesc('starts_on')->get(['id','name','code','is_current']);
        $sessionId=(int)$request->query('session_id',(int)($sessions->firstWhere('is_current',true)?->id??$sessions->first()?->id??0));
        $offerings=CollegeProgramOffering::query()->with('programTemplate:id,name,code')->where('college_id',$college->id)->when($sessionId,fn($q)=>$q->where('academic_session_id',$sessionId))->where('status','ACTIVE')->orderBy('program_template_id')->get(['id','program_template_id','academic_session_id'])->map(fn($o)=>['id'=>(int)$o->id,'name'=>$o->programTemplate?->name,'code'=>$o->programTemplate?->code])->values();
        $offeringId=(int)$request->query('offering_id',0);
        $targets=$service->targetsForOffering($college,$sessionId,$offeringId);
        return Inertia::render('college-student-imports/index',[
            'college'=>$college->only(['id','name','code']),'sessions'=>$sessions,'offerings'=>$offerings,'session_id'=>$sessionId,
            'importState'=>$service->state($college,(int)$request->user()->id,$request->query('token')),
            'targets'=>$targets,'offering_id'=>$offeringId,
            'importContext'=>$service->importContextForOffering($college,$sessionId,$offeringId),
            'savedMappings'=>StudentImportMapping::query()->where('college_id',$college->id)->when($offeringId,fn($q)=>$q->where('college_program_offering_id',$offeringId))->orderBy('name')->get(['id','name','college_program_offering_id','mapping','updated_at']),
            'can'=>['manage'=>$request->user()->hasCollegePermission('college_student_import.manage',$college->id)],
            'credentialsToken'=>preg_match('/^[A-Za-z0-9]{40}$/',(string)$request->query('credentials_token'))?(string)$request->query('credentials_token'):null,
        ]);
    }

    public function template(Request $request, College $college): HttpResponse
    {
        abort_unless($request->user()->hasCollegePermission('college_student_import.view',$college->id),403);
        $csv="full_name,date_of_birth,email,phone,student_uid,university_roll_no,class_roll_no,discipline_code\n";
        $csv.="Example Student,2005-01-31,student@example.edu,9999999999,,,,\n";
        return response($csv,200,['Content-Type'=>'text/csv; charset=UTF-8','Content-Disposition'=>'attachment; filename="student-import-template.csv"']);
    }

    public function upload(Request $request, College $college, StudentImportService $service): RedirectResponse
    {
        abort_unless($request->user()->hasCollegePermission('college_student_import.manage',$college->id),403);
        $data=$request->validate(['file'=>['required','file','max:5120','mimes:csv,txt']]);
        $token=$service->upload($college,(int)$request->user()->id,$data['file']);
        return redirect()->route('college-student-imports.index',['college'=>$college->id,'token'=>$token])->with('toast',['type'=>'success','message'=>'CSV uploaded. Map the columns and validate the preview.']);
    }

    public function preview(Request $request, College $college, StudentImportService $service): RedirectResponse
    {
        abort_unless($request->user()->hasCollegePermission('college_student_import.manage',$college->id),403);
        $data=$request->validate(['token'=>['required','string','size:40'],'session_id'=>['required','integer'],'offering_id'=>['required','integer'],'mapping'=>['required','array']]);
        $service->preview($college,(int)$request->user()->id,$data['token'],$data);
        return redirect()->route('college-student-imports.index',['college'=>$college->id,'token'=>$data['token'],'session_id'=>$data['session_id'],'offering_id'=>$data['offering_id']])->with('toast',['type'=>'success','message'=>'CSV validation completed. Review the preview before import.']);
    }

    public function store(Request $request, College $college, StudentImportService $service): RedirectResponse
    {
        abort_unless($request->user()->hasCollegePermission('college_student_import.manage',$college->id),403);
        $data=$request->validate(['token'=>['required','string','size:40'],'create_login_accounts'=>['nullable','boolean']]);
        $result=$service->import($college,(int)$request->user()->id,$data['token'],(int)$request->user()->id,$request->ip(),(bool)($data['create_login_accounts']??false));
        $params=['college'=>$college->id]; if($result['credentials_token']) $params['credentials_token']=$result['credentials_token'];
        $message=$result['count'].' student(s) imported successfully.'; if($result['login_accounts_created']) $message.=' '.$result['login_accounts_created'].' Student login account(s) created. Download the one-time credential sheet now.';
        return redirect()->route('college-student-imports.index',$params)->with('toast',['type'=>'success','message'=>$message]);
    }
    public function credentials(Request $request, College $college, string $token, StudentImportService $service): HttpResponse
    {
        abort_unless($request->user()->hasCollegePermission('college_student_import.manage',$college->id),403);
        $csv=$service->credentialsCsv($college,(int)$request->user()->id,$token);
        return response($csv,200,['Content-Type'=>'text/csv; charset=UTF-8','Content-Disposition'=>'attachment; filename="student-login-credentials.csv"','Cache-Control'=>'no-store, private']);
    }

    public function regenerateCredential(Request $request, College $college, StudentImportService $service): RedirectResponse
    {
        abort_unless($request->user()->hasCollegePermission('college_student_import.manage',$college->id),403);
        $data=$request->validate(['credential_email'=>['required','email','max:255']]);
        $token=$service->regenerateImportedStudentCredential($college,(int)$request->user()->id,$data['credential_email'],$request->ip());
        return redirect()->route('college-student-imports.index',['college'=>$college->id,'credentials_token'=>$token])
            ->with('toast',['type'=>'success','message'=>'A new temporary password was generated. The old password is now invalid. Download the one-time credential sheet now.']);
    }

    public function saveMapping(Request $request, College $college, StudentImportService $service): RedirectResponse
    {
        abort_unless($request->user()->hasCollegePermission('college_student_import.manage',$college->id),403);
        $data=$request->validate(['session_id'=>['required','integer'],'offering_id'=>['required','integer'],'name'=>['required','string','max:120'],'mapping'=>['required','array']]);
        $service->validateMapping($college,(int)$data['session_id'],(int)$data['offering_id'],$data['mapping']);
        StudentImportMapping::updateOrCreate(
            ['college_id'=>$college->id,'college_program_offering_id'=>(int)$data['offering_id'],'name'=>trim($data['name'])],
            ['mapping'=>array_filter($data['mapping'],fn($v)=>is_string($v)&&$v!==''),'created_by'=>$request->user()->id,'updated_by'=>$request->user()->id]
        );
        return back()->with('toast',['type'=>'success','message'=>'Import mapping saved. You can reuse it or download its CSV template anytime.']);
    }

    public function mappingTemplate(Request $request, College $college, StudentImportMapping $mapping): HttpResponse
    {
        abort_unless($request->user()->hasCollegePermission('college_student_import.view',$college->id),403);
        abort_unless((int)$mapping->college_id===(int)$college->id,404);
        $headers=array_values(array_unique(array_filter($mapping->mapping??[],fn($v)=>is_string($v)&&trim($v)!=='')));
        abort_if(empty($headers),404);
        $stream=fopen('php://temp','r+'); fputcsv($stream,$headers); rewind($stream); $csv=stream_get_contents($stream); fclose($stream);
        $filename=preg_replace('/[^A-Za-z0-9_-]+/','-',strtolower($mapping->name)).'-template.csv';
        return response($csv,200,['Content-Type'=>'text/csv; charset=UTF-8','Content-Disposition'=>'attachment; filename="'.$filename.'"']);
    }

}
