<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('student_enrollments', 'discipline_id')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->foreignId('discipline_id')->nullable()->after('college_program_offering_id')->constrained('academic_disciplines')->restrictOnDelete();
                $table->index(['college_id','college_program_offering_id','discipline_id','status'], 'student_enrollment_discipline_idx');
            });
        }

        $now=now();
        foreach ([
            ['college_student_import.view','view','View Student Import / Migration workspace',false],
            ['college_student_import.manage','manage','Upload, validate and import migrated students',true],
        ] as [$code,$action,$description,$sensitive]) {
            DB::table('permissions')->updateOrInsert(['code'=>$code],[
                'module'=>'Student Management','resource'=>'college_student_import','action'=>$action,'description'=>$description,
                'is_sensitive'=>$sensitive,'is_college_delegable'=>true,'status'=>'ACTIVE','created_at'=>$now,'updated_at'=>$now,
            ]);
            $pid=DB::table('permissions')->where('code',$code)->value('id');
            foreach(['SUPER_ADMIN','COLLEGE_ADMIN'] as $roleCode){
                $rid=DB::table('roles')->where('code',$roleCode)->where('status','ACTIVE')->value('id');
                if($rid&&$pid) DB::table('role_permissions')->updateOrInsert(['role_id'=>$rid,'permission_id'=>$pid],['created_at'=>$now,'updated_at'=>$now]);
            }
        }
    }

    public function down(): void
    {
        foreach(['college_student_import.view','college_student_import.manage'] as $code){
            $pid=DB::table('permissions')->where('code',$code)->value('id');
            if($pid) DB::table('role_permissions')->where('permission_id',$pid)->delete();
            DB::table('permissions')->where('code',$code)->delete();
        }
        if (Schema::hasColumn('student_enrollments','discipline_id')) {
            Schema::table('student_enrollments', function(Blueprint $table){
                $table->dropIndex('student_enrollment_discipline_idx');
                $table->dropConstrainedForeignId('discipline_id');
            });
        }
    }
};
