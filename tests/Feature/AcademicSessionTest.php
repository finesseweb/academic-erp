<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AcademicSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_authorized_user_can_manage_sessions_and_only_one_is_current(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/academic-sessions', ['name' => '2026-27', 'code' => 'AY2627', 'starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'status' => 'PLANNED'])->assertRedirect();
        $first = (int) \DB::table('academic_sessions')->value('id');
        $this->actingAs($admin)->post('/admin/academic-sessions', ['name' => '2027-28', 'code' => 'AY2728', 'starts_on' => '2027-07-01', 'ends_on' => '2028-06-30', 'status' => 'PLANNED'])->assertRedirect();
        $second = (int) \DB::table('academic_sessions')->where('id', '!=', $first)->value('id');
        $this->actingAs($admin)->patch("/admin/academic-sessions/{$first}/current")->assertRedirect();
        $this->actingAs($admin)->patch("/admin/academic-sessions/{$second}/current")->assertRedirect();
        $this->assertDatabaseHas('academic_sessions', ['id' => $first, 'is_current' => false]);
        $this->assertDatabaseHas('academic_sessions', ['id' => $second, 'is_current' => true]);
        $this->assertSame('2027-07-01', AcademicSession::findOrFail($second)->starts_on->format('Y-m-d'));
        $this->assertDatabaseHas('audit_logs', ['event' => 'ACADEMIC_SESSION_CURRENT_CHANGED', 'resource_id' => $second, 'scope_type' => 'UNIVERSITY']);
        $this->actingAs($admin)->get('/admin/academic-sessions')->assertOk()->assertInertia(fn (Assert $page) => $page->component('academic-sessions/index')->has('sessions', 2));
    }

    public function test_session_validation_and_authorization_are_enforced(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/academic-sessions', ['name' => 'Invalid', 'code' => 'INV', 'starts_on' => '2027-07-01', 'ends_on' => '2026-06-30', 'status' => 'PLANNED'])->assertSessionHasErrors('ends_on');
        $this->actingAs(User::factory()->create())->get('/admin/academic-sessions')->assertForbidden();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::whereCode('SUPER_ADMIN')->value('id'), ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $user;
    }
}
