<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Permission;
use App\Models\Role;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollegeAccessManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_university_operator_assigns_college_admin_who_can_login_only_into_assigned_college_context(): void
    {
        [$a, $b] = $this->colleges();
        $operator = $this->operator(['role.assign', 'college_admin.assign']);
        $rahul = User::factory()->create(['email' => 'rahul@example.test']);
        $role = Role::whereCode('COLLEGE_ADMIN')->firstOrFail();
        $this->actingAs($operator)->put("/admin/users/{$rahul->id}/roles", ['role_id' => $role->id, 'scope_type' => 'COLLEGE', 'college_id' => $a->id])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $rahul->id, 'account_type' => 'COLLEGE_STAFF', 'primary_college_id' => $a->id]);
        $this->actingAs($operator)->put("/admin/users/{$rahul->id}/roles", ['role_id' => $role->id, 'scope_type' => 'COLLEGE', 'college_id' => $b->id])->assertStatus(422);
        $another = User::factory()->create();
        $this->actingAs($operator)->put("/admin/users/{$another->id}/roles", ['role_id' => $role->id, 'scope_type' => 'UNIVERSITY'])->assertStatus(422);
        auth()->logout();
        $this->post('/login', ['email' => 'rahul@example.test', 'password' => 'password'])->assertRedirect();
        $this->get("/college/{$a->id}/users")->assertOk();
        $this->get("/college/{$b->id}/users")->assertForbidden();
    }

    public function test_college_admin_creates_only_own_college_users_and_roles(): void
    {
        [$a, $b] = $this->colleges();
        $admin = User::factory()->create(['primary_college_id' => $a->id, 'account_type' => 'COLLEGE_STAFF']);
        $admin->roles()->attach(Role::whereCode('COLLEGE_ADMIN')->value('id'), ['scope_type' => 'COLLEGE', 'scope_reference' => "college:{$a->id}", 'status' => 'ACTIVE']);
        $payload = ['name' => 'A Staff', 'email' => 'staff@a.test', 'mobile' => null, 'password' => 'Securepass123', 'password_confirmation' => 'Securepass123'];
        $this->actingAs($admin)->post("/college/{$a->id}/users", $payload)->assertRedirect();
        $staff = User::where('email', 'staff@a.test')->firstOrFail();
        $this->assertSame($a->id, $staff->primary_college_id);
        $this->actingAs($admin)->patch("/college/{$a->id}/users/{$staff->id}", ['name' => 'Updated Staff', 'email' => 'staff@a.test', 'mobile' => '1234567890'])->assertRedirect();
        $this->actingAs($admin)->patch("/college/{$a->id}/users/{$staff->id}/status", ['status' => 'INACTIVE'])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'name' => 'Updated Staff', 'status' => 'INACTIVE', 'primary_college_id' => $a->id]);
        $this->actingAs($admin)->post("/college/{$b->id}/users", [...$payload, 'email' => 'blocked@b.test'])->assertForbidden();
        $this->actingAs($admin)->post("/college/{$a->id}/roles", ['name' => 'Admissions Operator', 'code' => 'A_ADMISSIONS', 'description' => 'College admissions staff'])->assertRedirect();
        $collegeRole = Role::whereCode('A_ADMISSIONS')->firstOrFail();
        $this->assertSame("college:{$a->id}", $collegeRole->owner_scope_reference);
        $this->actingAs($admin)->patch("/college/{$a->id}/roles/{$collegeRole->id}", ['name' => 'Admissions Manager', 'code' => 'A_ADMISSIONS', 'description' => 'Updated'])->assertRedirect();
        $this->actingAs($admin)->patch("/college/{$a->id}/roles/{$collegeRole->id}/status", ['status' => 'INACTIVE'])->assertRedirect();
        $this->assertDatabaseHas('roles', ['id' => $collegeRole->id, 'name' => 'Admissions Manager', 'status' => 'INACTIVE']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'ROLE_CREATED', 'resource_id' => $collegeRole->id]);
        $this->actingAs($admin)->post("/college/{$b->id}/roles", ['name' => 'Blocked', 'code' => 'BLOCKED_B'])->assertForbidden();
        $otherUser = User::factory()->create(['primary_college_id' => $b->id, 'account_type' => 'COLLEGE_STAFF']);
        $this->actingAs($admin)->patch("/college/{$a->id}/users/{$otherUser->id}", ['name' => 'Tampered', 'email' => $otherUser->email])->assertNotFound();
    }

    private function colleges(): array
    {
        $u = University::firstOrFail();

        return [College::create(['university_id' => $u->id, 'name' => 'College A', 'code' => 'CA', 'affiliation_type' => 'Affiliated', 'status' => 'ACTIVE', 'country' => 'India', 'timezone' => 'Asia/Kolkata']), College::create(['university_id' => $u->id, 'name' => 'College B', 'code' => 'CB', 'affiliation_type' => 'Affiliated', 'status' => 'ACTIVE', 'country' => 'India', 'timezone' => 'Asia/Kolkata'])];
    }

    private function operator(array $codes): User
    {
        $u = User::factory()->create();
        $r = Role::create(['name' => 'University operator', 'code' => 'UNIV_OP_'.uniqid(), 'status' => 'ACTIVE']);
        $r->permissions()->sync(Permission::whereIn('code', $codes)->pluck('id'));
        $u->roles()->attach($r->id, ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $u;
    }
}
