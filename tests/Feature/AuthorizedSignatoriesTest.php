<?php

namespace Tests\Feature;

use App\Models\AuthorizedSignatory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthorizedSignatoriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_list_requires_authentication_and_permission(): void
    {
        $this->get('/admin/university/signatories')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/university/signatories')->assertForbidden();
    }

    public function test_authorized_user_can_search_and_filter_signatories(): void
    {
        $user = $this->userWithPermissions(['authorized_signatory.view']);
        $university = University::query()->firstOrFail();
        AuthorizedSignatory::query()->create($this->payload($university, ['full_name' => 'Asha Rao']));
        AuthorizedSignatory::query()->create($this->payload($university, ['full_name' => 'Dev Shah', 'authority_type' => 'FINANCE', 'status' => 'INACTIVE']));

        $this->actingAs($user)->get('/admin/university/signatories?search=Asha&authority_type=GENERAL&status=ACTIVE')
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('signatories/index')->has('signatories.data', 1)->where('signatories.data.0.full_name', 'Asha Rao')->where('summary.total', 2));
    }

    public function test_create_update_and_status_changes_are_authorized_and_audited(): void
    {
        $user = $this->userWithPermissions(['authorized_signatory.view', 'authorized_signatory.create', 'authorized_signatory.update', 'authorized_signatory.disable']);
        $payload = $this->payload(University::query()->firstOrFail());

        $this->actingAs($user)->post('/admin/university/signatories', $payload)->assertRedirect();
        $signatory = AuthorizedSignatory::query()->where('full_name', 'Academic Registrar')->firstOrFail();
        $this->assertDatabaseHas('audit_logs', ['event' => 'AUTHORIZED_SIGNATORY_CREATED', 'resource_id' => $signatory->id]);

        $payload['designation'] = 'Senior Registrar';
        $this->actingAs($user)->patch("/admin/university/signatories/{$signatory->id}", $payload)->assertRedirect();
        $this->assertDatabaseHas('authorized_signatories', ['id' => $signatory->id, 'designation' => 'Senior Registrar']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'AUTHORIZED_SIGNATORY_UPDATED', 'resource_id' => $signatory->id]);

        $this->actingAs($user)->patch("/admin/university/signatories/{$signatory->id}/status", ['status' => 'INACTIVE'])->assertRedirect();
        $this->assertDatabaseHas('authorized_signatories', ['id' => $signatory->id, 'status' => 'INACTIVE']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'AUTHORIZED_SIGNATORY_STATUS_CHANGED', 'resource_id' => $signatory->id]);
    }

    public function test_validation_and_action_permissions_are_enforced(): void
    {
        $viewer = $this->userWithPermissions(['authorized_signatory.view']);
        $this->actingAs($viewer)->get('/admin/university/signatories/create')->assertForbidden();
        $this->actingAs($viewer)->post('/admin/university/signatories', [])->assertForbidden();

        $creator = $this->userWithPermissions(['authorized_signatory.create']);
        $invalid = $this->payload(University::query()->firstOrFail(), ['effective_from' => '2026-08-20', 'effective_until' => '2026-08-19']);
        $this->actingAs($creator)->from('/admin/university/signatories/create')->post('/admin/university/signatories', $invalid)->assertSessionHasErrors(['effective_until']);
    }

    private function payload(University $university, array $overrides = []): array
    {
        return array_merge([
            'university_id' => $university->id, 'full_name' => 'Academic Registrar', 'designation' => 'Registrar',
            'authority_type' => 'GENERAL', 'email' => 'registrar@university.example', 'phone' => '+91 90000 00000',
            'effective_from' => '2026-08-19', 'effective_until' => '2030-08-18', 'status' => 'ACTIVE', 'notes' => 'University-wide authority.',
        ], $overrides);
    }

    private function userWithPermissions(array $codes): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'Signatory test role '.uniqid(), 'code' => 'SIGNATORY_TEST_'.strtoupper(uniqid()), 'status' => 'ACTIVE']);
        $role->permissions()->sync(Permission::query()->whereIn('code', $codes)->pluck('id'));
        $user->roles()->attach($role->id, ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $user;
    }
}
