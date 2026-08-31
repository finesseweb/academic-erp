<?php
namespace App\Services;
use App\Models\ApplicantProfile;
use App\Models\CollegeAdmissionApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ApplicantStudentPromotionService {
 /** Called by the final Admission Approval/Enrollment transaction once a real Student master row exists. */
 public function enableStudentAccess(CollegeAdmissionApplication $application, int $studentId): ApplicantProfile {
  if (!$application->applicant_user_id || $application->status!=='SUBMITTED') throw ValidationException::withMessages(['application'=>'Only a submitted applicant-owned application can be promoted to Student access.']);
  return DB::transaction(function() use($application,$studentId){
   $profile=ApplicantProfile::where('user_id',$application->applicant_user_id)->lockForUpdate()->firstOrFail();
   $profile->update(['lifecycle_status'=>'STUDENT_ENABLED','student_id'=>$studentId,'student_enabled_at'=>now()]);
   $profile->user()->update(['account_type'=>'STUDENT','status'=>'ACTIVE']);
   return $profile->fresh();
  });
 }
}
