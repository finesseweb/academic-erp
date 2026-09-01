<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class UpsertCollegeAdmissionInterviewRequest extends FormRequest {
 public function authorize():bool{return true;}
 public function rules():array{return ['panel_name'=>['required','string','max:150'],'scheduled_at'=>['required','date'],'venue'=>['nullable','string','max:255'],'status'=>['required','in:SCHEDULED,COMPLETED,CANCELLED'],'remarks'=>['nullable','string','max:3000'],'evaluators'=>['required','array','min:1'],'evaluators.*.user_id'=>['required','integer','distinct','exists:users,id'],'evaluators.*.raw_score'=>['nullable','numeric','min:0'],'evaluators.*.max_score'=>['nullable','numeric','min:0.001'],'evaluators.*.remarks'=>['nullable','string','max:2000']];}
}
