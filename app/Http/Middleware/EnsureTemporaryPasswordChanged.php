<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class EnsureTemporaryPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user=$request->user();
        if(!$user || !$user->must_change_password) return $next($request);
        if($request->routeIs('student.password.change','student.password.update','logout')) return $next($request);
        return redirect()->route('student.password.change');
    }
}
