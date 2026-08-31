<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  $now=now();
  foreach ([
   ['college_applicant_registration.view','Applicant Registration','applicant_registration','view','View applicant registration settings'],
   ['college_applicant_registration.settings','Applicant Registration','applicant_registration','settings','Manage applicant registration, email verification and CAPTCHA settings'],
  ] as $p) {
   DB::table('permissions')->updateOrInsert(['code'=>$p[0]],['module'=>$p[1],'resource'=>$p[2],'action'=>$p[3],'description'=>$p[4],'status'=>'ACTIVE','updated_at'=>$now,'created_at'=>$now]);
  }
  $role=DB::table('roles')->where('code','COLLEGE_ADMIN')->first();
  if($role){foreach(DB::table('permissions')->whereIn('code',['college_applicant_registration.view','college_applicant_registration.settings'])->pluck('id') as $pid) DB::table('role_permissions')->updateOrInsert(['role_id'=>$role->id,'permission_id'=>$pid],['created_at'=>$now,'updated_at'=>$now]);}
 }
 public function down(): void { $ids=DB::table('permissions')->whereIn('code',['college_applicant_registration.view','college_applicant_registration.settings'])->pluck('id'); DB::table('role_permissions')->whereIn('permission_id',$ids)->delete(); DB::table('permissions')->whereIn('id',$ids)->delete(); }
};
