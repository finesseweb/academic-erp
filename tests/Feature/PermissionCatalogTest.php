<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PermissionCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_catalog_requires_authentication_and_permission(): void
    {
        $this->get('/admin/permissions')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/permissions')->assertForbidden();
    }

    public function test_authorized_user_can_search_and_filter_catalog(): void
    {
        $user = $this->userWithPermission('permission.view');
        Permission::query()->where('code', 'user.reset_password')->update(['is_sensitive' => true]);

        $this->actingAs($user)->get('/admin/permissions?search=reset_password&module=Access%20%26%20Security&sensitive=yes&status=ACTIVE')
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('permissions/index')->has('permissions.data', 1)->where('permissions.data.0.code', 'user.reset_password')->has('modules')->where('summary.total', Permission::count()));
    }

    public function test_catalog_has_no_mutation_routes(): void
    {
        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('permissions.create'));
        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('permissions.store'));
        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('permissions.update'));
    }

    private function userWithPermission(string $code): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'Permission viewer', 'code' => 'PERMISSION_VIEWER_'.strtoupper(uniqid()), 'status' => 'ACTIVE']);
        $role->permissions()->sync(Permission::query()->where('code', $code)->pluck('id'));
        $user->roles()->attach($role->id, ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $user;
    }
}
