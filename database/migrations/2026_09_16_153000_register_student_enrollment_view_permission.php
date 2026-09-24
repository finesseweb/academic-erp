<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        DB::table('permissions')->updateOrInsert(['code'=>'college_student_enrollment.view'], [
            'module'=>'Student Enrollment','resource'=>'college_student_enrollment','action'=>'view',
            'description'=>'View the College-scoped Student Enrollment eligibility queue',
            'is_sensitive'=>false,'is_college_delegable'=>true,'status'=>'ACTIVE','created_at'=>$now,'updated_at'=>$now,
        ]);
        $pid = DB::table('permissions')->where('code','college_student_enrollment.view')->value('id');
        foreach (['SUPER_ADMIN','COLLEGE_ADMIN'] as $code) {
            $rid = DB::table('roles')->where('code',$code)->where('status','ACTIVE')->value('id');
            if ($rid && $pid) DB::table('role_permissions')->updateOrInsert(['role_id'=>$rid,'permission_id'=>$pid], ['created_at'=>$now,'updated_at'=>$now]);
        }
    }
    public function down(): void
    {
        $pid=DB::table('permissions')->where('code','college_student_enrollment.view')->value('id');
        if($pid) DB::table('role_permissions')->where('permission_id',$pid)->delete();
        DB::table('permissions')->where('code','college_student_enrollment.view')->delete();
    }
};
