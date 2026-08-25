<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $universityPermissions = [
        ['reservation_category.view', 'reservation_category', 'view', 'View Reservation / Quota Categories', false],
        ['reservation_category.create', 'reservation_category', 'create', 'Create Reservation / Quota Categories', false],
        ['reservation_category.update', 'reservation_category', 'update', 'Update Reservation / Quota Categories', false],
        ['reservation_category.enable', 'reservation_category', 'enable', 'Activate Reservation / Quota Categories', true],
        ['reservation_category.disable', 'reservation_category', 'disable', 'Deactivate Reservation / Quota Categories', true],
    ];

    private array $collegePermissions = [
        ['college_reservation.view', 'college_reservation', 'view', 'View College Reservation / Seat Distribution', false],
        ['college_reservation.create', 'college_reservation', 'create', 'Create College Reservation Plans', false],
        ['college_reservation.update', 'college_reservation', 'update', 'Update College Reservation Plans and allocations', false],
        ['college_reservation.enable', 'college_reservation', 'enable', 'Activate College Reservation Plans', true],
        ['college_reservation.disable', 'college_reservation', 'disable', 'Deactivate College Reservation Plans', true],
    ];

    public function up(): void
    {
        $now = now();
        foreach ($this->universityPermissions as [$code,$resource,$action,$description,$sensitive]) {
            DB::table('permissions')->updateOrInsert(['code'=>$code], [
                'resource'=>$resource,'action'=>$action,'module'=>'Academic Setup',
                'description'=>$description,'is_sensitive'=>$sensitive,'is_college_delegable'=>false,
                'status'=>'ACTIVE','created_at'=>$now,'updated_at'=>$now,
            ]);
        }
        foreach ($this->collegePermissions as [$code,$resource,$action,$description,$sensitive]) {
            DB::table('permissions')->updateOrInsert(['code'=>$code], [
                'resource'=>$resource,'action'=>$action,'module'=>'College Academic Setup',
                'description'=>$description,'is_sensitive'=>$sensitive,'is_college_delegable'=>true,
                'status'=>'ACTIVE','created_at'=>$now,'updated_at'=>$now,
            ]);
        }

        $super = DB::table('roles')->where('code','SUPER_ADMIN')->value('id');
        if ($super) {
            $ids = DB::table('permissions')->whereIn('code', array_merge(
                array_column($this->universityPermissions,0),
                array_column($this->collegePermissions,0)
            ))->pluck('id');
            foreach ($ids as $id) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id'=>$super,'permission_id'=>$id],
                    ['created_at'=>$now,'updated_at'=>$now]
                );
            }
        }

        $collegeAdmin = DB::table('roles')->where('code','COLLEGE_ADMIN')->value('id');
        if ($collegeAdmin) {
            $ids = DB::table('permissions')->whereIn('code', array_column($this->collegePermissions,0))->pluck('id');
            foreach ($ids as $id) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id'=>$collegeAdmin,'permission_id'=>$id],
                    ['created_at'=>$now,'updated_at'=>$now]
                );
            }
        }
    }

    public function down(): void
    {
        $codes = array_merge(array_column($this->universityPermissions,0), array_column($this->collegePermissions,0));
        $ids = DB::table('permissions')->whereIn('code',$codes)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id',$ids)->delete();
        DB::table('permissions')->whereIn('code',$codes)->delete();
    }
};
