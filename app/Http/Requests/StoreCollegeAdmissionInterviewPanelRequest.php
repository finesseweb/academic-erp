<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreCollegeAdmissionInterviewPanelRequest extends FormRequest {
 public function authorize():bool{return true;}
 public function rules():array{return [
  'name'=>['required','string','max:150'],
  'starts_at'=>['required','date'],
  'slot_duration_minutes'=>['required','integer','min:1','max:480'],
  'session_duration_hours'=>['required','numeric','min:0.25','max:24'],
  'venue'=>['nullable','string','max:255'],
  'breaks'=>['nullable','array','max:20'],
  'breaks.*.label'=>['nullable','string','max:100'],
  'breaks.*.start_time'=>['required','date_format:H:i'],
  'breaks.*.end_time'=>['required','date_format:H:i'],
  'evaluator_user_ids'=>['required','array','min:1'],
  'evaluator_user_ids.*'=>['required','integer','distinct','exists:users,id'],
  'choice_ids'=>['required','array','min:1','max:200'],
  'choice_ids.*'=>['required','integer','distinct','exists:college_admission_application_choices,id'],
 ];}
}
