<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('university_roll_no', 120)->nullable()->after('student_uid');
            $table->unique(['college_id', 'university_roll_no'], 'students_college_university_roll_uq');
        });
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->string('class_roll_no', 120)->nullable()->after('section_id');
            $table->unique(['college_program_offering_id', 'class_roll_no'], 'student_enrollment_class_roll_uq');
        });
        Schema::create('student_identity_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->unique()->constrained('colleges')->cascadeOnDelete();
            $table->string('student_uid_format', 160)->default('{COLLEGE_CODE}/STU/{YEAR}/{SEQ:6}');
            $table->string('university_roll_format', 160)->default('{YEAR}/{PROGRAM_CODE}/{SEQ:6}');
            $table->string('class_roll_format', 160)->default('{SEQ:3}');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('student_identity_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained('colleges')->cascadeOnDelete();
            $table->enum('identity_type', ['STUDENT_UID', 'UNIVERSITY_ROLL', 'CLASS_ROLL']);
            $table->string('scope_key', 120)->default('COLLEGE');
            $table->unsignedBigInteger('next_value')->default(1);
            $table->timestamps();
            $table->unique(['college_id', 'identity_type', 'scope_key'], 'student_identity_sequence_scope_uq');
        });

        $now = now();
        DB::table('permissions')->updateOrInsert(['code'=>'college_student_identity.manage'], [
            'module'=>'Student Management','resource'=>'college_student_identity','action'=>'manage',
            'description'=>'Configure and assign Student UID, University Roll and Class Roll identities',
            'is_sensitive'=>true,'is_college_delegable'=>true,'status'=>'ACTIVE','created_at'=>$now,'updated_at'=>$now,
        ]);
        $pid = DB::table('permissions')->where('code','college_student_identity.manage')->value('id');
        foreach (['SUPER_ADMIN','COLLEGE_ADMIN'] as $code) {
            $rid = DB::table('roles')->where('code',$code)->where('status','ACTIVE')->value('id');
            if ($rid && $pid) DB::table('role_permissions')->updateOrInsert(['role_id'=>$rid,'permission_id'=>$pid], ['created_at'=>$now,'updated_at'=>$now]);
        }
    }

    public function down(): void
    {
        $pid=DB::table('permissions')->where('code','college_student_identity.manage')->value('id');
        if($pid) DB::table('role_permissions')->where('permission_id',$pid)->delete();
        DB::table('permissions')->where('code','college_student_identity.manage')->delete();
        Schema::dropIfExists('student_identity_sequences');
        Schema::dropIfExists('student_identity_settings');
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropUnique('student_enrollment_class_roll_uq'); $table->dropColumn('class_roll_no');
        });
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_college_university_roll_uq'); $table->dropColumn('university_roll_no');
        });
    }
};
