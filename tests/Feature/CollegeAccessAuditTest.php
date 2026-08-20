<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\College;
use App\Models\Role;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CollegeAccessAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_college_admin_sees_only_their_college_audit_history(): void
    {
        [$collegeA, $collegeB] = $this->colleges();
        $admin = $this->collegeAdmin($collegeA);
        AuditLog::create(['actor_user_id' => $admin->id, 'event' => 'OWN_COLLEGE_EVENT', 'resource_type' => 'Role', 'scope_type' => 'COLLEGE', 'scope_reference' => "college:{$collegeA->id}", 'created_at' => now()]);
        AuditLog::create(['actor_user_id' => $admin->id, 'event' => 'OTHER_COLLEGE_EVENT', 'resource_type' => 'Role', 'scope_type' => 'COLLEGE', 'scope_reference' => "college:{$collegeB->id}", 'created_at' => now()]);
        AuditLog::create(['actor_user_id' => $admin->id, 'event' => 'UNSCOPED_EVENT', 'resource_type' => 'Role', 'created_at' => now()]);

        $this->actingAs($admin)->get("/college/{$collegeA->id}/audit-logs")
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('audit-logs/index')->where('context.title', 'College A Access Audit')->has('logs.data', 1)->where('logs.data.0.event', 'OWN_COLLEGE_EVENT'));
        $this->actingAs($admin)->get("/college/{$collegeB->id}/audit-logs")->assertForbidden();
        $this->actingAs(User::factory()->create())->get("/college/{$collegeA->id}/audit-logs")->assertForbidden();
    }

    private function colleges(): array
    {
        $university = University::firstOrFail();

        return [
            College::create(['university_id' => $university->id, 'name' => 'College A', 'code' => 'CA', 'affiliation_type' => 'Affiliated', 'status' => 'ACTIVE', 'country' => 'India', 'timezone' => 'Asia/Kolkata']),
            College::create(['university_id' => $university->id, 'name' => 'College B', 'code' => 'CB', 'affiliation_type' => 'Affiliated', 'status' => 'ACTIVE', 'country' => 'India', 'timezone' => 'Asia/Kolkata']),
        ];
    }

    private function collegeAdmin(College $college): User
    {
        $user = User::factory()->create(['account_type' => 'COLLEGE_STAFF', 'primary_college_id' => $college->id]);
        $user->roles()->attach(Role::whereCode('COLLEGE_ADMIN')->value('id'), ['scope_type' => 'COLLEGE', 'scope_reference' => "college:{$college->id}", 'status' => 'ACTIVE']);

        return $user;
    }
}
