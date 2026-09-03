<?php
namespace App\Http\Controllers;

use App\Models\ApplicantProfile;
use App\Models\CollegeApplicantRegistrationSetting;
use App\Models\CollegeAdmissionFormMapping;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Models\CollegeAdmissionCycle;
use App\Models\User;
use App\Notifications\ApplicantVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ApplicantPortalController extends Controller {
 public function gateway(Request $request,string $slug): Response|RedirectResponse {
  $mapping=$this->mapping($slug); $settings=CollegeApplicantRegistrationSetting::forCollege($mapping->college_id);
  if ($request->user()?->account_type==='STUDENT') return redirect()->route('student.portal');
  if ($request->user()?->account_type==='APPLICANT') return redirect()->route('applicant.application',['slug'=>$slug]);
  $captcha=$settings->captcha_required ? $this->captcha($request) : null;
  $college=College::with('university')->find($mapping->college_id);
  $offering=CollegeProgramOffering::with(['programTemplate','academicSession'])->find($mapping->college_program_offering_id);
  $cycle=CollegeAdmissionCycle::find($mapping->college_admission_cycle_id);
  return Inertia::render('public/admission-gateway',['portal'=>[
   'slug'=>$slug,
   'college_id'=>$mapping->college_id,
   'registration_enabled'=>$settings->registration_enabled,
   'email_verification_required'=>$settings->email_verification_required,
   'captcha_required'=>$settings->captcha_required,
   'captcha'=>$captcha,
   'institution'=>[
    'college_name'=>$college?->name ?? 'College Admissions',
    'college_code'=>$college?->code,
    'university_name'=>$college?->university?->name,
   ],
   'application_context'=>[
    'program_name'=>$offering?->programTemplate?->name,
    'program_code'=>$offering?->programTemplate?->code,
    'cycle_name'=>$cycle?->name,
    'cycle_code'=>$cycle?->code,
    'session_name'=>$offering?->academicSession?->name,
   ],
  ]]);
 }
 public function register(Request $request,string $slug): RedirectResponse {
  $mapping=$this->mapping($slug); $settings=CollegeApplicantRegistrationSetting::forCollege($mapping->college_id);
  if (!$settings->registration_enabled) throw ValidationException::withMessages(['registration'=>'Applicant registration is currently disabled by the College.']);
  $data=$request->validate(['name'=>['required','string','max:180'],'date_of_birth'=>['required','date','before_or_equal:today'],'email'=>['required','email','max:190','unique:users,email'],'phone'=>['required','string','max:40'],'password'=>['required','confirmed',Password::min(8)],'captcha_answer'=>['nullable','string','max:20']]);
  if ($settings->captcha_required && !$this->captchaValid($request,$data['captcha_answer']??'')) throw ValidationException::withMessages(['captcha_answer'=>'CAPTCHA answer is incorrect. Please try the new challenge.']);
  $user=User::create(['name'=>trim($data['name']),'email'=>strtolower(trim($data['email'])),'mobile'=>$data['phone'],'account_type'=>'APPLICANT','primary_college_id'=>$mapping->college_id,'status'=>'ACTIVE','password'=>$data['password']]);
  $profile=ApplicantProfile::create(['user_id'=>$user->id,'college_id'=>$mapping->college_id,'date_of_birth'=>$data['date_of_birth'],'phone'=>$data['phone']]);
  if (!$settings->email_verification_required) { $user->forceFill(['email_verified_at'=>now()])->save(); }
  else { $user->notify(new ApplicantVerifyEmail($slug)); }
  Auth::login($user); $request->session()->regenerate();
  if ($settings->email_verification_required) return redirect()->route('applicant.verify.notice',['slug'=>$slug]);
  return redirect()->route('applicant.application',['slug'=>$slug]);
 }
 public function login(Request $request,string $slug): RedirectResponse {
  $mapping=$this->mapping($slug); $data=$request->validate(['email'=>['required','email'],'password'=>['required','string']]);
  $user=User::where('email',strtolower($data['email']))->where('account_type','APPLICANT')->where('primary_college_id',$mapping->college_id)->where('status','ACTIVE')->first();
  if (!$user || !Hash::check($data['password'],$user->password)) throw ValidationException::withMessages(['email'=>'The applicant email or password is incorrect.']);
  Auth::login($user); $request->session()->regenerate();
  return redirect()->route('applicant.application',['slug'=>$slug]);
 }
 public function verificationNotice(Request $request,string $slug): Response|RedirectResponse {
  if (!$request->user() || $request->user()->account_type!=='APPLICANT') return redirect()->route('applicant.gateway',['slug'=>$slug]);
  if ($request->user()->email_verified_at) return redirect()->route('applicant.application',['slug'=>$slug]);
  return Inertia::render('applicant/verify-email',['slug'=>$slug,'email'=>$request->user()->email]);
 }
 public function verifyEmail(Request $request,string $slug,int $id,string $hash): RedirectResponse {
  $mapping=$this->mapping($slug);
  $user=User::whereKey($id)->where('account_type','APPLICANT')->where('primary_college_id',$mapping->college_id)->where('status','ACTIVE')->firstOrFail();
  abort_unless(hash_equals((string) $hash, sha1($user->getEmailForVerification())),403);
  if (!$user->hasVerifiedEmail()) $user->markEmailAsVerified();
  if (!$request->user() || (int)$request->user()->id !== (int)$user->id) { Auth::login($user); $request->session()->regenerate(); }
  return redirect()->route('applicant.application',['slug'=>$slug])->with('status','email-verified');
 }
 public function resendVerification(Request $request,string $slug): RedirectResponse {
  $user=$request->user(); abort_unless($user && $user->account_type==='APPLICANT',403);
  if (!$user->hasVerifiedEmail()) $user->notify(new ApplicantVerifyEmail($slug));
  return back()->with('status','verification-link-sent');
 }
 public function logout(Request $request,string $slug): RedirectResponse { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('applicant.gateway',['slug'=>$slug]); }
 private function mapping(string $slug): CollegeAdmissionFormMapping { return CollegeAdmissionFormMapping::where('public_slug',$slug)->where('public_enabled',true)->where('status','ACTIVE')->whereNotNull('college_id')->firstOrFail(); }
 private function captcha(Request $request): array { $a=random_int(2,9);$b=random_int(1,9);$request->session()->put('applicant_captcha_answer',(string)($a+$b));return ['question'=>"{$a} + {$b} = ?"]; }
 private function captchaValid(Request $request,string $answer): bool { $expected=(string)$request->session()->pull('applicant_captcha_answer',''); return $expected!=='' && hash_equals($expected,trim($answer)); }
}
