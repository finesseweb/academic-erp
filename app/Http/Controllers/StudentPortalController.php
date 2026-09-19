<?php
namespace App\Http\Controllers;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class StudentPortalController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user() && $request->user()->account_type==='STUDENT',403);
        $student=Student::query()->where('user_id',$request->user()->id)->where('status','ACTIVE')->with(['enrollments'=>fn($q)=>$q->where('status','ENROLLED')->latest('enrolled_at')])->firstOrFail();
        return Inertia::render('student/dashboard',['student'=>['name'=>$request->user()->name,'email'=>$request->user()->email,'student_id'=>$student->id,'enabled_at'=>$student->enrollments->first()?->enrolled_at?->toIso8601String()]]);
    }
}
