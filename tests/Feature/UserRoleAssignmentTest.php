<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Permission;
use App\Models\Role;
use App\Models\University;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_page_requires_user_and_role_view_permissions(): void
    {
        $target = User::factory()->create();
        $this->get("/admin/users/{$target->id}/roles")->assertRedirect('/login');
        $this->actingAs($this->operator(['user.view']))->get("/admin/users/{$target->id}/roles")->assertForbidden();
        $this->actingAs($this->operator(['user.view', 'role.view']))->get("/admin/users/{$target->id}/roles")->assertOk()->assertInertia(fn (Assert $p) => $p->component('users/roles')->where('managedUser.id', $target->id));
    }

    public function test_university_and_college_role_assignments_are_validated_and_audited(): void
    {
        $actor = $this->operator(['role.assign', 'role.unassign']);
        $target = User::factory()->create();
        $role = $this->customRole();
        $this->actingAs($actor)->put("/admin/users/{$target->id}/roles", ['role_id' => $role->id, 'scope_type' => 'UNIVERSITY'])->assertRedirect();
        $this->assertDatabaseHas('user_roles', ['user_id' => $target->id, 'role_id' => $role->id, 'scope_type' => 'UNIVERSITY', 'scope_reference' => 'university']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'USER_ROLE_ASSIGNED']);
        $college = College::create(['university_id' => University::firstOrFail()->id, 'name' => 'Scope College', 'code' => 'SCOPE', 'affiliation_type' => 'Affiliated', 'status' => 'ACTIVE', 'country' => 'India', 'timezone' => 'Asia/Kolkata']);
        $this->actingAs($actor)->put("/admin/users/{$target->id}/roles", ['role_id' => $role->id, 'scope_type' => 'COLLEGE', 'college_id' => $college->id])->assertRedirect();
        $assignment = UserRole::where('user_id', $target->id)->where('scope_type', 'COLLEGE')->firstOrFail();
        $this->assertSame("college:{$college->id}", $assignment->scope_reference);
        $this->actingAs($actor)->delete("/admin/users/{$target->id}/roles/{$assignment->id}")->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['event' => 'USER_ROLE_UNASSIGNED']);
    }

    public function test_duplicate_system_role_and_unassign_safety_rules_are_enforced(): void
    {
        $actor = $this->operator(['role.assign', 'role.unassign']);
        $target = User::factory()->create();
        $role = $this->customRole();
        $this->actingAs($actor)->put("/admin/users/{$target->id}/roles", ['role_id' => $role->id, 'scope_type' => 'UNIVERSITY'])->assertRedirect();
        $this->actingAs($actor)->put("/admin/users/{$target->id}/roles", ['role_id' => $role->id, 'scope_type' => 'UNIVERSITY'])->assertStatus(422);
        $system = Role::whereCode('SUPER_ADMIN')->firstOrFail();
        $this->actingAs($actor)->put("/admin/users/{$target->id}/roles", ['role_id' => $system->id, 'scope_type' => 'UNIVERSITY'])->assertSessionHasErrors('role_id');
        $own = UserRole::where('user_id', $actor->id)->firstOrFail();
        $this->actingAs($actor)->delete("/admin/users/{$actor->id}/roles/{$own->id}")->assertStatus(422);
    }

    private function customRole(): Role
    {
        return Role::create(['name' => 'Assigned role', 'code' => 'ASSIGNED_'.strtoupper(uniqid()), 'status' => 'ACTIVE', 'owner_scope_type' => 'UNIVERSITY', 'owner_scope_reference' => 'university']);
    }

    private function operator(array $codes): User
    {
        $u = User::factory()->create();
        $r = Role::create(['name' => 'Assignment operator', 'code' => 'ASSIGN_OPERATOR_'.strtoupper(uniqid()), 'status' => 'ACTIVE']);
        $r->permissions()->sync(Permission::whereIn('code', $codes)->pluck('id'));
        $u->roles()->attach($r->id, ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $u;
    }
}
