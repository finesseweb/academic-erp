<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DegreeLevelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_authorized_user_can_manage_ordered_degree_levels(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/degree-levels', ['name' => 'Undergraduate', 'code' => 'UG', 'description' => 'First degree programmes', 'display_order' => 10, 'status' => 'ACTIVE'])->assertRedirect();
        $level = (int) \DB::table('degree_levels')->value('id');
        $this->actingAs($admin)->patch("/admin/degree-levels/{$level}", ['name' => 'Undergraduate', 'code' => 'UG', 'description' => 'Bachelor-level programmes', 'display_order' => 5, 'status' => 'ACTIVE'])->assertRedirect();
        $this->actingAs($admin)->patch("/admin/degree-levels/{$level}/status", ['status' => 'INACTIVE'])->assertRedirect();
        $this->assertDatabaseHas('degree_levels', ['id' => $level, 'display_order' => 5, 'status' => 'INACTIVE']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'DEGREE_LEVEL_STATUS_CHANGED', 'resource_id' => $level, 'scope_type' => 'UNIVERSITY']);
        $this->actingAs($admin)->get('/admin/degree-levels')->assertOk()->assertInertia(fn (Assert $page) => $page->component('degree-levels/index')->has('levels', 1));
    }

    public function test_degree_level_unique_code_validation_and_authorization_are_enforced(): void
    {
        $admin = $this->admin();
        $payload = ['name' => 'Undergraduate', 'code' => 'UG', 'display_order' => 1, 'status' => 'ACTIVE'];
        $this->actingAs($admin)->post('/admin/degree-levels', $payload)->assertRedirect();
        $this->actingAs($admin)->post('/admin/degree-levels', $payload)->assertSessionHasErrors('code');
        $this->actingAs(User::factory()->create())->get('/admin/degree-levels')->assertForbidden();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::whereCode('SUPER_ADMIN')->value('id'), ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $user;
    }
}
