<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditLogPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_page_requires_audit_view_and_has_no_mutation_routes(): void
    {
        $this->get('/admin/audit-logs')->assertRedirect('/login');
        $this->actingAs($this->operator(false))->get('/admin/audit-logs')->assertForbidden();
        $this->actingAs($this->operator())->get('/admin/audit-logs')->assertOk();
        $this->post('/admin/audit-logs')->assertMethodNotAllowed();
        $this->delete('/admin/audit-logs/1')->assertNotFound();
    }

    public function test_events_are_paginated_filtered_and_return_safe_details(): void
    {
        $actor = $this->operator();
        DB::table('audit_logs')->insert(['actor_user_id' => $actor->id, 'event' => 'ROLE_UPDATED', 'resource_type' => 'Role', 'resource_id' => 42, 'before' => json_encode(['name' => 'Old']), 'after' => json_encode(['name' => 'New']), 'ip_address' => '127.0.0.1', 'created_at' => '2026-08-20 10:00:00']);
        DB::table('audit_logs')->insert(['event' => 'OTHER_EVENT', 'resource_type' => 'User', 'created_at' => '2026-08-19 10:00:00']);

        $this->actingAs($actor)->get('/admin/audit-logs?event=ROLE_UPDATED&resource_type=Role&from=2026-08-20&until=2026-08-20')
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('audit-logs/index')->has('logs.data', 1)->where('logs.data.0.event', 'ROLE_UPDATED')->where('logs.data.0.before.name', 'Old'));
    }

    private function operator(bool $allowed = true): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Audit operator', 'code' => 'AUDIT_'.strtoupper(uniqid()), 'status' => 'ACTIVE']);
        if ($allowed) {
            $role->permissions()->attach(Permission::where('code', 'audit.view')->value('id'));
        }
        $user->roles()->attach($role->id, ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $user;
    }
}
