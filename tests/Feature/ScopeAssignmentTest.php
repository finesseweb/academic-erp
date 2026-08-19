<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Permission;
use App\Models\Role;
use App\Models\University;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScopeAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_scope_update_requires_its_sensitive_permission(): void
    {
        $target = User::factory()->create();
        $assignment = $this->assignment($target);

        $this->actingAs($this->operator([]))
            ->patch("/admin/users/{$target->id}/roles/{$assignment->id}/scope", $this->payload())
            ->assertForbidden();
    }

    public function test_scope_status_and_effective_period_are_validated_updated_and_audited(): void
    {
        $actor = $this->operator(['scope.update']);
        $target = User::factory()->create();
        $assignment = $this->assignment($target);
        $college = College::create([
            'university_id' => University::firstOrFail()->id,
            'name' => 'Assigned College',
            'code' => 'ASSIGNED',
            'affiliation_type' => 'Affiliated',
            'status' => 'ACTIVE',
            'country' => 'India',
            'timezone' => 'Asia/Kolkata',
        ]);

        $this->actingAs($actor)
            ->patch("/admin/users/{$target->id}/roles/{$assignment->id}/scope", $this->payload([
                'scope_type' => 'COLLEGE',
                'college_id' => $college->id,
                'effective_from' => '2026-08-20',
                'effective_until' => '2026-09-20',
            ]))
            ->assertRedirect();

        $assignment->refresh();
        $this->assertSame('COLLEGE', $assignment->scope_type);
        $this->assertSame("college:{$college->id}", $assignment->scope_reference);
        $this->assertSame('2026-08-20 00:00:00', $assignment->effective_from?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-20 23:59:59', $assignment->effective_until?->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('audit_logs', ['event' => 'USER_ROLE_SCOPE_UPDATED', 'resource_id' => $assignment->id]);

        $this->actingAs($actor)
            ->patch("/admin/users/{$target->id}/roles/{$assignment->id}/scope", $this->payload([
                'effective_from' => '2026-09-20',
                'effective_until' => '2026-08-20',
            ]))
            ->assertSessionHasErrors('effective_until');
    }

    public function test_duplicate_inactive_college_system_and_self_changes_are_blocked(): void
    {
        $actor = $this->operator(['scope.update']);
        $target = User::factory()->create();
        $role = $this->customRole();
        $first = UserRole::create(['user_id' => $target->id, 'role_id' => $role->id, 'scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);
        $college = College::create([
            'university_id' => University::firstOrFail()->id,
            'name' => 'Inactive College',
            'code' => 'INACTIVE',
            'affiliation_type' => 'Affiliated',
            'status' => 'INACTIVE',
            'country' => 'India',
            'timezone' => 'Asia/Kolkata',
        ]);

        $this->actingAs($actor)
            ->patch("/admin/users/{$target->id}/roles/{$first->id}/scope", $this->payload(['scope_type' => 'COLLEGE', 'college_id' => $college->id]))
            ->assertSessionHasErrors('college_id');

        $second = UserRole::create(['user_id' => $target->id, 'role_id' => $role->id, 'scope_type' => 'COLLEGE', 'scope_reference' => 'college:99', 'status' => 'ACTIVE']);
        $this->actingAs($actor)
            ->patch("/admin/users/{$target->id}/roles/{$second->id}/scope", $this->payload())
            ->assertStatus(422);

        $systemAssignment = UserRole::create(['user_id' => $target->id, 'role_id' => Role::whereCode('SUPER_ADMIN')->value('id'), 'scope_type' => 'COLLEGE', 'scope_reference' => 'college:100', 'status' => 'ACTIVE']);
        $this->actingAs($actor)
            ->patch("/admin/users/{$target->id}/roles/{$systemAssignment->id}/scope", $this->payload())
            ->assertStatus(422);

        $ownAssignment = UserRole::where('user_id', $actor->id)->firstOrFail();
        $this->actingAs($actor)
            ->patch("/admin/users/{$actor->id}/roles/{$ownAssignment->id}/scope", $this->payload())
            ->assertStatus(422);
    }

    public function test_permissions_obey_assignment_status_and_effective_dates(): void
    {
        Carbon::setTestNow('2026-08-19 12:00:00');
        $user = User::factory()->create();
        $permission = Permission::where('code', 'user.view')->firstOrFail();
        $role = $this->customRole();
        $role->permissions()->attach($permission->id);
        $assignment = UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => 'UNIVERSITY',
            'scope_reference' => 'university',
            'status' => 'ACTIVE',
            'effective_from' => now()->addDay(),
        ]);

        $this->assertFalse($user->hasPermission('user.view'));
        $assignment->update(['effective_from' => now()->subDay(), 'effective_until' => now()->subMinute()]);
        $this->assertFalse($user->hasPermission('user.view'));
        $assignment->update(['effective_until' => now()->addDay()]);
        $this->assertTrue($user->hasPermission('user.view'));
        $assignment->update(['status' => 'INACTIVE']);
        $this->assertFalse($user->hasPermission('user.view'));
        Carbon::setTestNow();
    }

    private function payload(array $overrides = []): array
    {
        return [...[
            'scope_type' => 'UNIVERSITY',
            'status' => 'ACTIVE',
            'effective_from' => null,
            'effective_until' => null,
        ], ...$overrides];
    }

    private function assignment(User $user): UserRole
    {
        return UserRole::create(['user_id' => $user->id, 'role_id' => $this->customRole()->id, 'scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);
    }

    private function customRole(): Role
    {
        return Role::create(['name' => 'Scoped role', 'code' => 'SCOPED_'.strtoupper(uniqid()), 'status' => 'ACTIVE', 'owner_scope_type' => 'UNIVERSITY', 'owner_scope_reference' => 'university']);
    }

    private function operator(array $codes): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Scope operator', 'code' => 'SCOPE_OPERATOR_'.strtoupper(uniqid()), 'status' => 'ACTIVE']);
        $role->permissions()->sync(Permission::whereIn('code', $codes)->pluck('id'));
        $user->roles()->attach($role->id, ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $user;
    }
}
