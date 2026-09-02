<?php
namespace App\Services;
use App\Models\{College,CollegeAdmissionApplicationChoice,CollegeAdmissionInterview,User};
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Validation\ValidationException;
class CollegeAdmissionInterviewService {
 public function save(College $college,CollegeAdmissionApplicationChoice $choice,array $data,int $actorId,?string $ip,CollegeAdmissionScoreService $scoreService):CollegeAdmissionInterview {
  $choice->load(['application','selectionRule','score']);$application=$choice->application;$rule=$choice->selectionRule;
  if(!$application||(int)$application->college_id!==(int)$college->id)abort(404);
  if($application->status!=='SUBMITTED'||$choice->eligibility_status!=='ELIGIBLE')throw ValidationException::withMessages(['interview'=>'Interview is available only for SUBMITTED + ELIGIBLE choices.']);
  if(!$rule||(float)$rule->interview_weight_percent<=0)throw ValidationException::withMessages(['interview'=>'The locked Selection Rule does not require Interview.']);
  if(!$choice->score)throw ValidationException::withMessages(['interview'=>'Complete Score Capture / Normalization before scheduling the Interview.']);
  $this->assertNoDownstream($choice);
  $evaluators=collect($data['evaluators']);
  $users=User::query()->whereIn('id',$evaluators->pluck('user_id'))
   ->where('primary_college_id',$college->id)
   ->where('account_type','COLLEGE_STAFF')
   ->where('status','ACTIVE')
   ->whereHas('roles',fn($q)=>$q->where('roles.status','ACTIVE')->where('user_roles.status','ACTIVE')->where('user_roles.scope_type','COLLEGE')->where('user_roles.scope_reference',"college:{$college->id}")->where(fn($r)=>$r->whereNull('user_roles.effective_from')->orWhere('user_roles.effective_from','<=',now()))->where(fn($r)=>$r->whereNull('user_roles.effective_until')->orWhere('user_roles.effective_until','>=',now())))
   ->get()->keyBy('id');
  if($users->count()!==$evaluators->count())throw ValidationException::withMessages(['evaluators'=>'Every evaluator must be an ACTIVE College Staff user with an active role assignment in this College. Applicant/Student accounts cannot be interview evaluators.']);
  $completed=$data['status']==='COMPLETED';$normalized=[];
  foreach($evaluators as $i=>$e){$raw=$e['raw_score']??null;$max=$e['max_score']??null;if($completed&&($raw===null||$max===null))throw ValidationException::withMessages(["evaluators.$i.raw_score"=>'Raw and maximum score are required for every evaluator when completing an Interview.']);if($raw!==null||$max!==null){$raw=(float)$raw;$max=(float)$max;if($max<=0||$raw<0||$raw>$max)throw ValidationException::withMessages(["evaluators.$i.raw_score"=>'Evaluator raw score must be between 0 and maximum score.']);$normalized[$i]=round(($raw/$max)*100,3);}}
  return DB::transaction(function()use($application,$choice,$rule,$data,$actorId,$ip,$evaluators,$users,$normalized,$completed,$scoreService,$college){
   $existing=CollegeAdmissionInterview::where('college_admission_application_choice_id',$choice->id)->first();$before=$existing?->load('evaluators')->toArray();
   $final=$completed&&count($normalized)?round(array_sum($normalized)/count($normalized),3):null;
   $interview=CollegeAdmissionInterview::updateOrCreate(['college_admission_application_choice_id'=>$choice->id],['college_admission_application_id'=>$application->id,'college_admission_selection_rule_id'=>$rule->id,'panel_name'=>trim($data['panel_name']),'scheduled_at'=>$data['scheduled_at'],'venue'=>filled($data['venue']??null)?trim($data['venue']):null,'status'=>$data['status'],'final_raw_score'=>null,'final_max_score'=>null,'normalized_score'=>$final,'remarks'=>filled($data['remarks']??null)?trim($data['remarks']):null,'evaluated_at'=>$completed?now():null,'created_by'=>$existing?->created_by??$actorId,'updated_by'=>$actorId]);
   $interview->evaluators()->delete();foreach($evaluators as $i=>$e){$u=$users[(int)$e['user_id']];$interview->evaluators()->create(['evaluator_user_id'=>$u->id,'evaluator_name_snapshot'=>$u->name,'raw_score'=>$e['raw_score']??null,'max_score'=>$e['max_score']??null,'normalized_score'=>$normalized[$i]??null,'remarks'=>filled($e['remarks']??null)?trim($e['remarks']):null]);}
   $scoreService->applyInterviewScore($college,$choice,$completed?$final:null,$actorId,$ip);
   $after=$interview->fresh('evaluators')->toArray();DB::table('audit_logs')->insert(['actor_user_id'=>$actorId,'event'=>$before?'COLLEGE_ADMISSION_INTERVIEW_UPDATED':'COLLEGE_ADMISSION_INTERVIEW_CREATED','resource_type'=>'CollegeAdmissionInterview','resource_id'=>$interview->id,'scope_type'=>'COLLEGE','scope_reference'=>'college:'.$college->id,'before'=>$before?json_encode($before):null,'after'=>json_encode($after),'ip_address'=>$ip,'created_at'=>now()]);return $interview->fresh('evaluators');
  });
 }
 private function assertNoDownstream(CollegeAdmissionApplicationChoice $choice):void{foreach([['college_admission_merit_entries','college_admission_application_choice_id'],['college_admission_seat_allocations','college_admission_application_choice_id'],['admissions','college_admission_application_choice_id']]as[$table,$column])if(Schema::hasTable($table)&&Schema::hasColumn($table,$column)&&DB::table($table)->where($column,$choice->id)->exists())throw ValidationException::withMessages(['interview'=>'Interview result is already consumed by downstream Merit / Seat / Admission processing.']);}
}
