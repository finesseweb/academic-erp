<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_list_requires_authentication_and_permission(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden();
    }

    public function test_authorized_user_can_filter_users(): void
    {
        $admin = $this->userWithPermissions(['user.view']);
        User::factory()->create(['name' => 'Inactive Person', 'email' => 'inactive@example.test', 'status' => 'INACTIVE', 'account_type' => 'OTHER']);
        $this->actingAs($admin)->get('/admin/users?search=Inactive&status=INACTIVE&account_type=OTHER')->assertOk()->assertInertia(fn (Assert $p) => $p->component('users/index')->has('users.data', 1)->where('users.data.0.email', 'inactive@example.test'));
    }

    public function test_create_update_status_and_reset_are_audited(): void
    {
        Notification::fake();
        $admin = $this->userWithPermissions(['user.view', 'user.create', 'user.update', 'user.disable', 'user.enable', 'user.reset_password']);
        $payload = ['name' => 'Managed User', 'email' => 'managed@example.test', 'mobile' => '9999999999', 'account_type' => 'UNIVERSITY_STAFF', 'status' => 'ACTIVE', 'password' => 'StrongPassword123', 'password_confirmation' => 'StrongPassword123'];
        $this->actingAs($admin)->post('/admin/users', $payload)->assertRedirect();
        $user = User::whereEmail('managed@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('StrongPassword123', $user->password));
        $this->assertDatabaseHas('audit_logs', ['event' => 'USER_CREATED', 'resource_id' => $user->id]);
        $this->actingAs($admin)->patch("/admin/users/{$user->id}", ['name' => 'Managed User Updated', 'email' => $user->email, 'mobile' => '8888888888', 'account_type' => 'OTHER'])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['event' => 'USER_UPDATED', 'resource_id' => $user->id]);
        $this->actingAs($admin)->patch("/admin/users/{$user->id}/status", ['status' => 'INACTIVE'])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'status' => 'INACTIVE']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'USER_DISABLED', 'resource_id' => $user->id]);
        $this->actingAs($admin)->post("/admin/users/{$user->id}/password-reset")->assertRedirect();
        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertDatabaseHas('audit_logs', ['event' => 'USER_PASSWORD_RESET_INITIATED', 'resource_id' => $user->id]);
    }

    public function test_validation_permissions_and_protected_accounts_are_enforced(): void
    {
        $viewer = $this->userWithPermissions(['user.view']);
        $this->actingAs($viewer)->post('/admin/users', [])->assertForbidden();
        $creator = $this->userWithPermissions(['user.create']);
        $this->actingAs($creator)->from('/admin/users/create')->post('/admin/users', [])->assertSessionHasErrors(['name', 'email', 'account_type', 'status', 'password']);
        $super = User::factory()->create();
        $super->roles()->attach(Role::query()->where('code', 'SUPER_ADMIN')->value('id'), ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);
        $operator = $this->userWithPermissions(['user.disable']);
        $this->actingAs($operator)->patch("/admin/users/{$super->id}/status", ['status' => 'INACTIVE'])->assertStatus(422);
    }

    public function test_inactive_user_is_logged_out_of_protected_pages(): void
    {
        $user = User::factory()->create(['status' => 'INACTIVE']);
        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    private function userWithPermissions(array $codes): User
    {
        $u = User::factory()->create();
        $r = Role::create(['name' => 'User admin test '.uniqid(), 'code' => 'USER_ADMIN_'.strtoupper(uniqid()), 'status' => 'ACTIVE']);
        $r->permissions()->sync(Permission::whereIn('code', $codes)->pluck('id'));
        $u->roles()->attach($r->id, ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $u;
    }
}
