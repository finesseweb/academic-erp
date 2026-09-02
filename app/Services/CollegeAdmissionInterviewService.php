<?php
namespace App\Services;
use App\Models\{College,CollegeAdmissionApplicationChoice,CollegeAdmissionInterview,User};
use App\Notifications\ApplicantInterviewScheduleNotification;
use Illuminate\Support\Facades\{DB,Log,Notification,Schema};
use Illuminate\Validation\ValidationException;
class CollegeAdmissionInterviewService {
 public function save(College $college,CollegeAdmissionApplicationChoice $choice,array $data,int $actorId,?string $ip,CollegeAdmissionScoreService $scoreService):CollegeAdmissionInterview {
  $choice->load(['application.applicantUser','selectionRule','score']);$application=$choice->application;$rule=$choice->selectionRule;
  if(!$application||(int)$application->college_id!==(int)$college->id)abort(404);
  if($application->status!=='SUBMITTED'||$choice->eligibility_status!=='ELIGIBLE')throw ValidationException::withMessages(['interview'=>'Interview is available only for SUBMITTED + ELIGIBLE choices.']);
  if(!$rule||(float)$rule->interview_weight_percent<=0)throw ValidationException::withMessages(['interview'=>'The locked Selection Rule does not require Interview.']);
  if(!$choice->score)throw ValidationException::withMessages(['interview'=>'Complete Score Capture / Normalization before scheduling the Interview.']);
  $this->assertNoDownstream($choice);
  $linkedInterview=CollegeAdmissionInterview::query()->with('panel.evaluators')->where('college_admission_application_choice_id',$choice->id)->first();
  $evaluators=collect($data['evaluators']);
  if($linkedInterview?->panel){$expected=$linkedInterview->panel->evaluators->pluck('evaluator_user_id')->map(fn($id)=>(int)$id)->sort()->values()->all();$submitted=$evaluators->pluck('user_id')->map(fn($id)=>(int)$id)->sort()->values()->all();if($expected!==$submitted)throw ValidationException::withMessages(['evaluators'=>'This Interview is linked to a reusable panel. Its evaluator roster is controlled by that panel and cannot be changed candidate-by-candidate.']);}
  $users=User::query()->whereIn('id',$evaluators->pluck('user_id'))
   ->where('primary_college_id',$college->id)
   ->where('account_type','COLLEGE_STAFF')
   ->where('status','ACTIVE')
   ->whereHas('roles',fn($q)=>$q->where('roles.status','ACTIVE')->where('user_roles.status','ACTIVE')->where('user_roles.scope_type','COLLEGE')->where('user_roles.scope_reference',"college:{$college->id}")->where(fn($r)=>$r->whereNull('user_roles.effective_from')->orWhere('user_roles.effective_from','<=',now()))->where(fn($r)=>$r->whereNull('user_roles.effective_until')->orWhere('user_roles.effective_until','>=',now()))->where(fn($eligible)=>$eligible->where('roles.code','COLLEGE_ADMIN')->orWhereHas('permissions',fn($p)=>$p->where('permissions.code','college_admission_interview.evaluate')->where('permissions.status','ACTIVE'))))
   ->get()->keyBy('id');
  if($users->count()!==$evaluators->count())throw ValidationException::withMessages(['evaluators'=>'Every evaluator must be ACTIVE College Staff with an active College role granting Interview Evaluator permission. College Administrator is always eligible; Applicant/Student accounts cannot be interview evaluators.']);
  $completed=$data['status']==='COMPLETED';$normalized=[];
  foreach($evaluators as $i=>$e){$raw=$e['raw_score']??null;$max=$e['max_score']??null;if($completed&&($raw===null||$max===null))throw ValidationException::withMessages(["evaluators.$i.raw_score"=>'Raw and maximum score are required for every evaluator when completing an Interview.']);if($raw!==null||$max!==null){$raw=(float)$raw;$max=(float)$max;if($max<=0||$raw<0||$raw>$max)throw ValidationException::withMessages(["evaluators.$i.raw_score"=>'Evaluator raw score must be between 0 and maximum score.']);$normalized[$i]=round(($raw/$max)*100,3);}}
  [$interview,$mailEvent]=DB::transaction(function()use($application,$choice,$rule,$data,$actorId,$ip,$evaluators,$users,$normalized,$completed,$scoreService,$college){
   $existing=CollegeAdmissionInterview::where('college_admission_application_choice_id',$choice->id)->with('panel')->first();$before=$existing?->load('evaluators')->toArray();
   $previous=$existing?['status'=>$existing->status,'panel_name'=>$existing->panel_name,'scheduled_at'=>$existing->scheduled_at?->toIso8601String(),'venue'=>$existing->venue]:null;
   $final=$completed&&count($normalized)?round(array_sum($normalized)/count($normalized),3):null;
   $interview=CollegeAdmissionInterview::updateOrCreate(['college_admission_application_choice_id'=>$choice->id],['college_admission_application_id'=>$application->id,'college_admission_selection_rule_id'=>$rule->id,'panel_name'=>$existing?->panel?->name??trim($data['panel_name']),'scheduled_at'=>$data['scheduled_at'],'venue'=>$existing?->panel?->venue??(filled($data['venue']??null)?trim($data['venue']):null),'status'=>$data['status'],'final_raw_score'=>null,'final_max_score'=>null,'normalized_score'=>$final,'remarks'=>filled($data['remarks']??null)?trim($data['remarks']):null,'evaluated_at'=>$completed?now():null,'created_by'=>$existing?->created_by??$actorId,'updated_by'=>$actorId]);
   $interview->evaluators()->delete();foreach($evaluators as $i=>$e){$u=$users[(int)$e['user_id']];$interview->evaluators()->create(['evaluator_user_id'=>$u->id,'evaluator_name_snapshot'=>$u->name,'raw_score'=>$e['raw_score']??null,'max_score'=>$e['max_score']??null,'normalized_score'=>$normalized[$i]??null,'remarks'=>filled($e['remarks']??null)?trim($e['remarks']):null]);}
   $scoreService->applyInterviewScore($college,$choice,$completed?$final:null,$actorId,$ip);
   $fresh=$interview->fresh('evaluators');
   $mailEvent=$this->mailEvent($previous,$fresh);
   $after=$fresh->toArray();DB::table('audit_logs')->insert(['actor_user_id'=>$actorId,'event'=>$before?'COLLEGE_ADMISSION_INTERVIEW_UPDATED':'COLLEGE_ADMISSION_INTERVIEW_CREATED','resource_type'=>'CollegeAdmissionInterview','resource_id'=>$interview->id,'scope_type'=>'COLLEGE','scope_reference'=>'college:'.$college->id,'before'=>$before?json_encode($before):null,'after'=>json_encode($after),'ip_address'=>$ip,'created_at'=>now()]);return [$fresh,$mailEvent];
  });
  if($mailEvent)$this->sendCandidateMail($college,$application,$interview,$mailEvent);
  return $interview;
 }
 private function mailEvent(?array $previous,CollegeAdmissionInterview $interview):?string{
  if($interview->status==='CANCELLED')return (!$previous||$previous['status']!=='CANCELLED')?'CANCELLED':null;
  if($interview->status!=='SCHEDULED')return null;
  if(!$previous)return 'SCHEDULED';
  $changed=$previous['status']!=='SCHEDULED'||$previous['panel_name']!==$interview->panel_name||$previous['venue']!==$interview->venue||$previous['scheduled_at']!==$interview->scheduled_at?->toIso8601String();
  return $changed?'RESCHEDULED':null;
 }
 private function sendCandidateMail(College $college,$application,CollegeAdmissionInterview $interview,string $event):void{
  $email=$application->applicantUser?->email?:$application->email;if(!filter_var($email,FILTER_VALIDATE_EMAIL))return;
  $timezone=$college->timezone?:config('app.timezone','UTC');$scheduled=$interview->scheduled_at?->copy()->timezone($timezone)->format('d M Y, h:i A T')??'-';
  try{Notification::route('mail',$email)->notify(new ApplicantInterviewScheduleNotification($event,$application->candidate_name?:($application->applicantUser?->name?:'Applicant'),$application->application_no?:('APP-'.$application->id),$college->name,$interview->panel_name,$scheduled,$interview->venue));}
  catch(\Throwable $e){Log::warning('Admission interview notification email failed',['college_id'=>$college->id,'application_id'=>$application->id,'interview_id'=>$interview->id,'event'=>$event,'message'=>$e->getMessage()]);}
 }
 private function assertNoDownstream(CollegeAdmissionApplicationChoice $choice):void{foreach([['college_admission_merit_entries','college_admission_application_choice_id'],['college_admission_seat_allocations','college_admission_application_choice_id'],['admissions','college_admission_application_choice_id']]as[$table,$column])if(Schema::hasTable($table)&&Schema::hasColumn($table,$column)&&DB::table($table)->where($column,$choice->id)->exists())throw ValidationException::withMessages(['interview'=>'Interview result is already consumed by downstream Merit / Seat / Admission processing.']);}
}
