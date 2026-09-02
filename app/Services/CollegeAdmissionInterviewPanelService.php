<?php
namespace App\Services;
use App\Models\{College,CollegeAdmissionApplicationChoice,CollegeAdmissionInterview,CollegeAdmissionInterviewPanel,User};
use App\Notifications\ApplicantInterviewScheduleNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB,Log,Notification};
use Illuminate\Validation\ValidationException;
class CollegeAdmissionInterviewPanelService {
 public function createAndAssign(College $college,array $data,int $actorId,?string $ip):CollegeAdmissionInterviewPanel {
  $choiceIds=collect($data['choice_ids'])->map(fn($id)=>(int)$id)->unique()->values();
  $evaluatorIds=collect($data['evaluator_user_ids'])->map(fn($id)=>(int)$id)->unique()->values();
  $evaluators=$this->eligibleEvaluators($college,$evaluatorIds->all());
  if($evaluators->count()!==$evaluatorIds->count())throw ValidationException::withMessages(['evaluator_user_ids'=>'Every panel evaluator must be ACTIVE College Staff with an active College role granting Interview Evaluator permission. College Administrator is always eligible; Applicant/Student accounts cannot be evaluators.']);
  $choices=CollegeAdmissionApplicationChoice::query()->with(['application.applicantUser','selectionRule','score','interview'])->whereIn('id',$choiceIds)->get()->keyBy('id');
  if($choices->count()!==$choiceIds->count())throw ValidationException::withMessages(['choice_ids'=>'One or more selected candidates are unavailable.']);
  foreach($choiceIds as $id){$this->assertSchedulable($college,$choices[$id]);}

  $start=Carbon::parse($data['starts_at']);
  $slotDuration=(int)$data['slot_duration_minutes'];
  $sessionDuration=(int)round(((float)$data['session_duration_hours'])*60);
  if($sessionDuration<$slotDuration)throw ValidationException::withMessages(['session_duration_hours'=>'Panel working duration must be at least one full candidate slot.']);
  $panelEnd=$start->copy()->addMinutes($sessionDuration);
  if($panelEnd->format('Y-m-d')!==$start->format('Y-m-d'))throw ValidationException::withMessages(['session_duration_hours'=>'Panel working duration must end on the same calendar day as the panel start.']);
  $breaks=$this->normalizeBreaks($data['breaks']??[],$start,$panelEnd);
  $slots=$this->buildSlots($start,$panelEnd,$slotDuration,$breaks,$choiceIds->count());

  $panel=DB::transaction(function()use($college,$data,$actorId,$ip,$choiceIds,$choices,$evaluators,$start,$slotDuration,$sessionDuration,$breaks,$slots){
   $panel=CollegeAdmissionInterviewPanel::create(['college_id'=>$college->id,'name'=>trim($data['name']),'starts_at'=>$start,'slot_duration_minutes'=>$slotDuration,'session_duration_minutes'=>$sessionDuration,'venue'=>filled($data['venue']??null)?trim($data['venue']):null,'status'=>'ACTIVE','created_by'=>$actorId,'updated_by'=>$actorId]);
   foreach($evaluators as $u)$panel->evaluators()->create(['evaluator_user_id'=>$u->id,'evaluator_name_snapshot'=>$u->name]);
   foreach($breaks as $break)$panel->breakPeriods()->create(['label'=>$break['label'],'starts_at'=>$break['starts_at'],'ends_at'=>$break['ends_at']]);
   foreach($choiceIds as $sequence=>$choiceId){$choice=$choices[$choiceId];$interview=CollegeAdmissionInterview::create(['college_admission_application_id'=>$choice->application->id,'college_admission_application_choice_id'=>$choice->id,'college_admission_selection_rule_id'=>$choice->selectionRule->id,'college_admission_interview_panel_id'=>$panel->id,'slot_sequence'=>$sequence+1,'panel_name'=>$panel->name,'scheduled_at'=>$slots[$sequence],'venue'=>$panel->venue,'status'=>'SCHEDULED','created_by'=>$actorId,'updated_by'=>$actorId]);foreach($evaluators as $u)$interview->evaluators()->create(['evaluator_user_id'=>$u->id,'evaluator_name_snapshot'=>$u->name]);}
   DB::table('audit_logs')->insert(['actor_user_id'=>$actorId,'event'=>'COLLEGE_ADMISSION_INTERVIEW_PANEL_CREATED','resource_type'=>'CollegeAdmissionInterviewPanel','resource_id'=>$panel->id,'scope_type'=>'COLLEGE','scope_reference'=>'college:'.$college->id,'before'=>null,'after'=>json_encode(['panel'=>$panel->toArray(),'choice_ids'=>$choiceIds->all(),'evaluator_user_ids'=>$evaluators->pluck('id')->all(),'breaks'=>collect($breaks)->map(fn($b)=>['label'=>$b['label'],'starts_at'=>$b['starts_at']->toIso8601String(),'ends_at'=>$b['ends_at']->toIso8601String()])->all()]),'ip_address'=>$ip,'created_at'=>now()]);return $panel->fresh(['evaluators','breakPeriods','interviews.application.applicantUser']);
  });
  foreach($panel->interviews as $interview)$this->sendCandidateMail($college,$interview,'SCHEDULED');
  return $panel;
 }
 private function normalizeBreaks(array $rows,Carbon $panelStart,Carbon $panelEnd):array{
  $date=$panelStart->format('Y-m-d');$timezone=$panelStart->getTimezone();$normalized=[];
  foreach($rows as $i=>$row){$from=Carbon::createFromFormat('Y-m-d H:i',$date.' '.$row['start_time'],$timezone);$to=Carbon::createFromFormat('Y-m-d H:i',$date.' '.$row['end_time'],$timezone);if(!$to->gt($from))throw ValidationException::withMessages(["breaks.$i.end_time"=>'Break end time must be after break start time.']);if($from->lt($panelStart)||$to->gt($panelEnd))throw ValidationException::withMessages(["breaks.$i.start_time"=>'Every break must fall completely inside the panel working window.']);$normalized[]=['label'=>filled($row['label']??null)?trim($row['label']):null,'starts_at'=>$from,'ends_at'=>$to];}
  usort($normalized,fn($a,$b)=>$a['starts_at']->getTimestamp()<=>$b['starts_at']->getTimestamp());for($i=1;$i<count($normalized);$i++)if($normalized[$i]['starts_at']->lt($normalized[$i-1]['ends_at']))throw ValidationException::withMessages(['breaks'=>'Interview panel breaks cannot overlap each other.']);return $normalized;
 }
 private function buildSlots(Carbon $panelStart,Carbon $panelEnd,int $slotDuration,array $breaks,int $required):array{
  $slots=[];$cursor=$panelStart->copy();
  while(count($slots)<$required){$slotEnd=$cursor->copy()->addMinutes($slotDuration);if($slotEnd->gt($panelEnd))throw ValidationException::withMessages(['choice_ids'=>'The selected candidates do not fit inside this panel working duration after excluding breaks. Increase panel duration, reduce selected candidates, reduce slot duration, or adjust breaks.']);$overlap=null;foreach($breaks as $break)if($cursor->lt($break['ends_at'])&&$slotEnd->gt($break['starts_at'])){$overlap=$break;break;}if($overlap){$cursor=$overlap['ends_at']->copy();continue;}$slots[]=$cursor->copy();$cursor=$slotEnd;}
  return $slots;
 }
 private function eligibleEvaluators(College $college,array $ids){return User::query()->whereIn('id',$ids)->where('primary_college_id',$college->id)->where('account_type','COLLEGE_STAFF')->where('status','ACTIVE')->whereHas('roles',fn($q)=>$q->where('roles.status','ACTIVE')->where('user_roles.status','ACTIVE')->where('user_roles.scope_type','COLLEGE')->where('user_roles.scope_reference',"college:{$college->id}")->where(fn($r)=>$r->whereNull('user_roles.effective_from')->orWhere('user_roles.effective_from','<=',now()))->where(fn($r)=>$r->whereNull('user_roles.effective_until')->orWhere('user_roles.effective_until','>=',now()))->where(fn($eligible)=>$eligible->where('roles.code','COLLEGE_ADMIN')->orWhereHas('permissions',fn($p)=>$p->where('permissions.code','college_admission_interview.evaluate')->where('permissions.status','ACTIVE'))))->get()->keyBy('id');}
 private function assertSchedulable(College $college,CollegeAdmissionApplicationChoice $choice):void{$application=$choice->application;$rule=$choice->selectionRule;if(!$application||(int)$application->college_id!==(int)$college->id)throw ValidationException::withMessages(['choice_ids'=>'A selected candidate is outside this College.']);if($application->status!=='SUBMITTED'||$choice->eligibility_status!=='ELIGIBLE')throw ValidationException::withMessages(['choice_ids'=>'Every selected candidate must be SUBMITTED and ELIGIBLE.']);if(!$rule||(float)$rule->interview_weight_percent<=0)throw ValidationException::withMessages(['choice_ids'=>'Every selected candidate must have a locked Selection Rule requiring Interview.']);if(!$choice->score)throw ValidationException::withMessages(['choice_ids'=>'Complete Score Capture before scheduling every selected candidate.']);if($choice->interview)throw ValidationException::withMessages(['choice_ids'=>'One or more selected candidates already have an Interview schedule. Use Manage Interview for existing schedules.']);}
 private function sendCandidateMail(College $college,CollegeAdmissionInterview $interview,string $event):void{$application=$interview->application;$email=$application?->applicantUser?->email?:$application?->email;if(!filter_var($email,FILTER_VALIDATE_EMAIL))return;$timezone=$college->timezone?:config('app.timezone','UTC');$scheduled=$interview->scheduled_at?->copy()->timezone($timezone)->format('d M Y, h:i A T')??'-';try{Notification::route('mail',$email)->notify(new ApplicantInterviewScheduleNotification($event,$application->candidate_name?:($application->applicantUser?->name?:'Applicant'),$application->application_no?:('APP-'.$application->id),$college->name,$interview->panel_name,$scheduled,$interview->venue));}catch(\Throwable $e){Log::warning('Bulk interview schedule notification email failed',['college_id'=>$college->id,'application_id'=>$application?->id,'interview_id'=>$interview->id,'message'=>$e->getMessage()]);}}
}
