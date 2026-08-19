<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_list_requires_authentication_and_permission(): void
    {
        $this->get('/admin/roles')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/roles')->assertForbidden();
    }

    public function test_authorized_user_can_filter_roles(): void
    {
        $user = $this->userWithPermissions(['role.view']);
        Role::create(['name' => 'Inactive Reviewer', 'code' => 'INACTIVE_REVIEWER', 'is_system_role' => false, 'owner_scope_type' => 'UNIVERSITY', 'owner_scope_reference' => 'university', 'status' => 'INACTIVE']);
        $this->actingAs($user)->get('/admin/roles?search=Reviewer&type=CUSTOM&status=INACTIVE')->assertOk()->assertInertia(fn (Assert $p) => $p->component('roles/index')->has('roles.data', 1)->where('roles.data.0.code', 'INACTIVE_REVIEWER'));
    }

    public function test_create_update_and_status_are_audited(): void
    {
        $user = $this->userWithPermissions(['role.view', 'role.create', 'role.update', 'role.disable']);
        $payload = ['name' => 'Academic Reviewer', 'code' => 'ACADEMIC_REVIEWER', 'description' => 'Reviews academic records', 'status' => 'ACTIVE'];
        $this->actingAs($user)->post('/admin/roles', $payload)->assertRedirect();
        $role = Role::whereCode('ACADEMIC_REVIEWER')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['event' => 'ROLE_CREATED', 'resource_id' => $role->id]);
        $this->actingAs($user)->patch("/admin/roles/{$role->id}", ['name' => 'Senior Academic Reviewer', 'code' => $role->code, 'description' => 'Updated'])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['event' => 'ROLE_UPDATED', 'resource_id' => $role->id]);
        $this->actingAs($user)->patch("/admin/roles/{$role->id}/status", ['status' => 'INACTIVE'])->assertRedirect();
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'status' => 'INACTIVE']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'ROLE_STATUS_CHANGED', 'resource_id' => $role->id]);
    }

    public function test_validation_permissions_and_system_role_protection(): void
    {
        $viewer = $this->userWithPermissions(['role.view']);
        $this->actingAs($viewer)->post('/admin/roles', [])->assertForbidden();
        $creator = $this->userWithPermissions(['role.create']);
        $this->actingAs($creator)->from('/admin/roles/create')->post('/admin/roles', ['code' => 'invalid code'])->assertSessionHasErrors(['name', 'code', 'status']);
        $manager = $this->userWithPermissions(['role.update', 'role.disable']);
        $system = Role::whereCode('SUPER_ADMIN')->firstOrFail();
        $this->actingAs($manager)->patch("/admin/roles/{$system->id}", ['name' => 'Changed', 'code' => 'CHANGED'])->assertStatus(422);
        $this->actingAs($manager)->patch("/admin/roles/{$system->id}/status", ['status' => 'INACTIVE'])->assertStatus(422);
    }

    private function userWithPermissions(array $codes): User
    {
        $u = User::factory()->create();
        $r = Role::create(['name' => 'Role test '.uniqid(), 'code' => 'ROLE_TEST_'.strtoupper(uniqid()), 'status' => 'ACTIVE']);
        $r->permissions()->sync(Permission::whereIn('code', $codes)->pluck('id'));
        $u->roles()->attach($r->id, ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $u;
    }
}
