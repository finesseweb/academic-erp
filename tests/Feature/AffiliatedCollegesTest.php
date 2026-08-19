<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Permission;
use App\Models\Role;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AffiliatedCollegesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_list_requires_authentication_and_permission(): void
    {
        $this->get('/admin/colleges')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/colleges')->assertForbidden();
    }

    public function test_authorized_user_can_search_and_filter_paginated_colleges(): void
    {
        $user = $this->userWithPermissions(['college.view']);
        $university = University::query()->firstOrFail();
        College::query()->create($this->payload($university, ['name' => 'North College', 'code' => 'NORTH']));
        College::query()->create($this->payload($university, ['name' => 'South College', 'code' => 'SOUTH', 'status' => 'INACTIVE']));

        $this->actingAs($user)->get('/admin/colleges?search=North&status=ACTIVE')
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('colleges/index')->has('colleges.data', 1)->where('colleges.data.0.code', 'NORTH')->where('summary.total', 2));
    }

    public function test_create_update_and_status_changes_are_authorized_and_audited(): void
    {
        $user = $this->userWithPermissions(['college.view', 'college.create', 'college.update', 'college.disable']);
        $payload = $this->payload(University::query()->firstOrFail());

        $this->actingAs($user)->post('/admin/colleges', $payload)->assertRedirect();
        $college = College::query()->where('code', 'ACAD')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['event' => 'COLLEGE_CREATED', 'resource_id' => $college->id]);

        $payload['name'] = 'Academic College Updated';
        $this->actingAs($user)->patch("/admin/colleges/{$college->id}", $payload)->assertRedirect();
        $this->assertDatabaseHas('colleges', ['id' => $college->id, 'name' => 'Academic College Updated']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'COLLEGE_UPDATED', 'resource_id' => $college->id]);

        $this->actingAs($user)->patch("/admin/colleges/{$college->id}/status", ['status' => 'INACTIVE'])->assertRedirect();
        $this->assertDatabaseHas('colleges', ['id' => $college->id, 'status' => 'INACTIVE']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'COLLEGE_STATUS_CHANGED', 'resource_id' => $college->id]);
    }

    public function test_validation_and_action_specific_permissions_are_enforced(): void
    {
        $viewer = $this->userWithPermissions(['college.view']);
        $this->actingAs($viewer)->get('/admin/colleges/create')->assertForbidden();
        $this->actingAs($viewer)->post('/admin/colleges', [])->assertForbidden();

        $creator = $this->userWithPermissions(['college.create']);
        $this->actingAs($creator)->from('/admin/colleges/create')->post('/admin/colleges', [])->assertSessionHasErrors(['name', 'code', 'affiliation_type', 'status', 'country', 'timezone']);
    }

    private function payload(University $university, array $overrides = []): array
    {
        return array_merge([
            'university_id' => $university->id, 'name' => 'Academic College', 'code' => 'ACAD',
            'affiliation_type' => 'Affiliated', 'status' => 'ACTIVE', 'official_email' => 'office@college.example',
            'official_phone' => '+91 90000 00000', 'website' => 'https://college.example',
            'address_line_1' => 'University Road', 'city' => 'Pune', 'state' => 'Maharashtra',
            'postal_code' => '411001', 'country' => 'India', 'timezone' => 'Asia/Kolkata',
        ], $overrides);
    }

    private function userWithPermissions(array $codes): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'College test role '.uniqid(), 'code' => 'COLLEGE_TEST_'.strtoupper(uniqid()), 'status' => 'ACTIVE']);
        $role->permissions()->sync(Permission::query()->whereIn('code', $codes)->pluck('id'));
        $user->roles()->attach($role->id, ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $user;
    }
}
