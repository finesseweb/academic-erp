<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request; use Inertia\Inertia; use Inertia\Response;
class StudentPortalController extends Controller { public function index(Request $request): Response { abort_unless($request->user() && $request->user()->account_type==='STUDENT',403); $profile=\App\Models\ApplicantProfile::where('user_id',$request->user()->id)->firstOrFail(); return Inertia::render('student/dashboard',['student'=>['name'=>$request->user()->name,'email'=>$request->user()->email,'student_id'=>$profile->student_id,'enabled_at'=>$profile->student_enabled_at?->toIso8601String()]]); } }
