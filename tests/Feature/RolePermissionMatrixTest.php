<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RolePermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_matrix_requires_view_permissions(): void
    {
        $role = $this->customRole();
        $this->get("/admin/roles/{$role->id}/permissions")->assertRedirect('/login');
        $this->actingAs($this->userWithPermissions(['role.view']))->get("/admin/roles/{$role->id}/permissions")->assertForbidden();
        $this->actingAs($this->userWithPermissions(['role.view', 'permission.view']))->get("/admin/roles/{$role->id}/permissions")
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('roles/permissions')->where('role.code', $role->code)->has('permissions'));
    }

    public function test_permissions_can_be_added_removed_and_audited(): void
    {
        $actor = $this->userWithPermissions(['permission.assign_to_role', 'permission.remove_from_role']);
        $role = $this->customRole();
        $first = Permission::where('code', 'user.view')->firstOrFail();
        $second = Permission::where('code', 'role.view')->firstOrFail();
        $role->permissions()->attach($first->id);

        $this->actingAs($actor)->put("/admin/roles/{$role->id}/permissions", ['permission_ids' => [$second->id]])->assertRedirect();
        $this->assertDatabaseMissing('role_permissions', ['role_id' => $role->id, 'permission_id' => $first->id]);
        $this->assertDatabaseHas('role_permissions', ['role_id' => $role->id, 'permission_id' => $second->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'ROLE_PERMISSIONS_UPDATED', 'resource_id' => $role->id]);
    }

    public function test_diff_specific_permissions_and_active_catalog_validation_are_enforced(): void
    {
        $role = $this->customRole();
        $permission = Permission::where('code', 'user.view')->firstOrFail();
        $assigner = $this->userWithPermissions(['permission.assign_to_role']);
        $this->actingAs($assigner)->put("/admin/roles/{$role->id}/permissions", ['permission_ids' => [$permission->id]])->assertRedirect();
        $this->actingAs($assigner)->put("/admin/roles/{$role->id}/permissions", ['permission_ids' => []])->assertForbidden();

        $permission->update(['status' => 'INACTIVE']);
        $other = $this->customRole();
        $this->actingAs($assigner)->put("/admin/roles/{$other->id}/permissions", ['permission_ids' => [$permission->id]])->assertStatus(422);
    }

    public function test_system_role_permissions_cannot_be_mutated(): void
    {
        $actor = $this->userWithPermissions(['permission.assign_to_role', 'permission.remove_from_role']);
        $system = Role::where('code', 'SUPER_ADMIN')->firstOrFail();
        $this->actingAs($actor)->put("/admin/roles/{$system->id}/permissions", ['permission_ids' => []])->assertStatus(422);
    }

    private function customRole(): Role
    {
        return Role::create(['name' => 'Matrix target '.uniqid(), 'code' => 'MATRIX_'.strtoupper(uniqid()), 'status' => 'ACTIVE', 'owner_scope_type' => 'UNIVERSITY', 'owner_scope_reference' => 'university']);
    }

    private function userWithPermissions(array $codes): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Matrix operator '.uniqid(), 'code' => 'MATRIX_OPERATOR_'.strtoupper(uniqid()), 'status' => 'ACTIVE']);
        $role->permissions()->sync(Permission::whereIn('code', $codes)->pluck('id'));
        $user->roles()->attach($role->id, ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $user;
    }
}
