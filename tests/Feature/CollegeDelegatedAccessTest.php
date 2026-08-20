<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Permission;
use App\Models\Role;
use App\Models\University;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CollegeDelegatedAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_college_role_permissions_are_delegable_owned_and_cannot_exceed_actor_grants(): void
    {
        [$collegeA, $collegeB] = $this->colleges();
        $admin = $this->collegeAdmin($collegeA);
        $roleA = $this->collegeRole($collegeA, 'A_OPERATOR');
        $roleB = $this->collegeRole($collegeB, 'B_OPERATOR');
        $delegable = Permission::whereCode('college_user.view')->firstOrFail();

        $this->actingAs($admin)->get("/college/{$collegeA->id}/roles/{$roleA->id}/permissions")
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('roles/permissions')->where('context.updateUrl', "/college/{$collegeA->id}/roles/{$roleA->id}/permissions"));
        $this->actingAs($admin)->put("/college/{$collegeA->id}/roles/{$roleA->id}/permissions", ['permission_ids' => [$delegable->id]])->assertRedirect();
        $this->assertDatabaseHas('role_permissions', ['role_id' => $roleA->id, 'permission_id' => $delegable->id]);
        $universityPermission = Permission::whereCode('university.update')->firstOrFail();
        $this->actingAs($admin)->put("/college/{$collegeA->id}/roles/{$roleA->id}/permissions", ['permission_ids' => [$universityPermission->id]])->assertStatus(422);
        $notHeld = Permission::create(['code' => 'college_test.delegable', 'module' => 'College Test', 'resource' => 'college_test', 'action' => 'delegable', 'description' => 'Test only', 'is_college_delegable' => true, 'status' => 'ACTIVE']);
        $this->actingAs($admin)->put("/college/{$collegeA->id}/roles/{$roleA->id}/permissions", ['permission_ids' => [$notHeld->id]])->assertForbidden();
        $this->actingAs($admin)->get("/college/{$collegeA->id}/roles/{$roleB->id}/permissions")->assertNotFound();
        $this->assertDatabaseHas('audit_logs', ['event' => 'ROLE_PERMISSIONS_UPDATED', 'resource_id' => $roleA->id]);
    }

    public function test_college_user_role_and_lifecycle_are_fixed_to_same_college(): void
    {
        [$collegeA, $collegeB] = $this->colleges();
        $admin = $this->collegeAdmin($collegeA);
        $staff = User::factory()->create(['account_type' => 'COLLEGE_STAFF', 'primary_college_id' => $collegeA->id]);
        $otherStaff = User::factory()->create(['account_type' => 'COLLEGE_STAFF', 'primary_college_id' => $collegeB->id]);
        $roleA = $this->collegeRole($collegeA, 'A_STAFF');
        $roleB = $this->collegeRole($collegeB, 'B_STAFF');

        $this->actingAs($admin)->post("/college/{$collegeA->id}/users/{$staff->id}/roles", ['role_id' => $roleA->id])->assertRedirect();
        $assignment = UserRole::where('user_id', $staff->id)->where('role_id', $roleA->id)->firstOrFail();
        $this->assertSame("college:{$collegeA->id}", $assignment->scope_reference);
        $this->actingAs($admin)->patch("/college/{$collegeA->id}/users/{$staff->id}/roles/{$assignment->id}/scope", ['status' => 'ACTIVE', 'effective_from' => '2026-08-20', 'effective_until' => '2026-12-31'])->assertRedirect();
        $this->assertSame('2026-12-31 23:59:59', $assignment->fresh()->effective_until?->format('Y-m-d H:i:s'));
        $this->actingAs($admin)->post("/college/{$collegeA->id}/users/{$staff->id}/roles", ['role_id' => $roleB->id])->assertSessionHasErrors('role_id');
        $this->actingAs($admin)->get("/college/{$collegeA->id}/users/{$otherStaff->id}/roles")->assertNotFound();
        $this->actingAs($admin)->delete("/college/{$collegeA->id}/users/{$staff->id}/roles/{$assignment->id}")->assertRedirect();
        $this->assertDatabaseMissing('user_roles', ['id' => $assignment->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'USER_ROLE_SCOPE_UPDATED', 'resource_id' => $assignment->id]);
    }

    private function colleges(): array
    {
        $university = University::firstOrFail();

        return [
            College::create(['university_id' => $university->id, 'name' => 'College A', 'code' => 'CA', 'affiliation_type' => 'Affiliated', 'status' => 'ACTIVE', 'country' => 'India', 'timezone' => 'Asia/Kolkata']),
            College::create(['university_id' => $university->id, 'name' => 'College B', 'code' => 'CB', 'affiliation_type' => 'Affiliated', 'status' => 'ACTIVE', 'country' => 'India', 'timezone' => 'Asia/Kolkata']),
        ];
    }

    private function collegeAdmin(College $college): User
    {
        $user = User::factory()->create(['account_type' => 'COLLEGE_STAFF', 'primary_college_id' => $college->id]);
        $user->roles()->attach(Role::whereCode('COLLEGE_ADMIN')->value('id'), ['scope_type' => 'COLLEGE', 'scope_reference' => "college:{$college->id}", 'status' => 'ACTIVE']);

        return $user;
    }

    private function collegeRole(College $college, string $code): Role
    {
        return Role::create(['name' => str_replace('_', ' ', $code), 'code' => $code, 'is_system_role' => false, 'owner_scope_type' => 'COLLEGE', 'owner_scope_reference' => "college:{$college->id}", 'status' => 'ACTIVE']);
    }
}
