<?php
namespace App\Http\Controllers;
use App\Concerns\PasswordValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class StudentPasswordController extends Controller
{
    use PasswordValidationRules;
    public function edit(Request $request): Response
    {
        abort_unless($request->user()?->account_type==='STUDENT' && $request->user()?->must_change_password,403);
        return Inertia::render('student/change-password',['email'=>$request->user()->email]);
    }
    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->account_type==='STUDENT' && $request->user()?->must_change_password,403);
        $data=$request->validate(['current_password'=>$this->currentPasswordRules(),'password'=>$this->passwordRules()]);
        $request->user()->update(['password'=>$data['password'],'must_change_password'=>false]);
        return redirect()->route('student.portal')->with('toast',['type'=>'success','message'=>'Password changed. Welcome to your Student Portal.']);
    }
}
