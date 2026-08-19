<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UniversityProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/university')->assertRedirect('/login');
    }

    public function test_user_without_view_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/university')
            ->assertForbidden();
    }

    public function test_authorized_user_can_view_university_profile(): void
    {
        $user = $this->userWithPermissions(['university.view']);

        $this->actingAs($user)
            ->get('/admin/university')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('university/profile')
                ->where('university.code', 'UNIVERSITY')
                ->where('can.update', false));
    }

    public function test_authorized_user_can_update_profile_and_audit_is_written(): void
    {
        $user = $this->userWithPermissions(['university.view', 'university.update']);
        $university = University::query()->firstOrFail();

        $payload = [
            'name' => 'National Academic University', 'code' => 'NAU', 'short_name' => 'NAU',
            'established_on' => '2001-07-10', 'university_type' => 'State University',
            'accreditation' => 'NAAC A+', 'official_email' => 'office@nau.example',
            'official_phone' => '+91 98765 43210', 'website' => 'https://nau.example',
            'address_line_1' => 'Knowledge Campus', 'address_line_2' => null,
            'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411001',
            'country' => 'India', 'timezone' => 'Asia/Kolkata',
        ];

        $this->actingAs($user)
            ->patch(route('university.update', $university), $payload)
            ->assertRedirect()
            ->assertSessionHas('toast.message', 'University profile updated.');

        $savedUniversity = University::query()->findOrFail($university->id);
        $this->assertSame('National Academic University', $savedUniversity->name);
        $this->assertSame('NAU', $savedUniversity->code);
        $this->assertSame('2001-07-10', $savedUniversity->established_on?->toDateString());
        $this->assertDatabaseHas('audit_logs', ['actor_user_id' => $user->id, 'event' => 'UNIVERSITY_UPDATED', 'resource_id' => $university->id]);
    }

    public function test_establishment_date_is_returned_to_inertia_as_a_date_only_value(): void
    {
        $user = $this->userWithPermissions(['university.view']);
        University::query()->firstOrFail()->update(['established_on' => '2001-07-10']);

        $this->actingAs($user)->get('/admin/university')->assertInertia(
            fn (Assert $page) => $page->where('university.established_on', '2001-07-10'),
        );
    }

    public function test_other_university_type_requires_and_stores_specific_value(): void
    {
        $user = $this->userWithPermissions(['university.view', 'university.update']);
        $university = University::query()->firstOrFail();
        $payload = [
            'name' => 'Academic University', 'code' => 'AU', 'country' => 'India',
            'timezone' => 'Asia/Kolkata', 'university_type' => 'Other',
        ];

        $this->actingAs($user)->from('/admin/university')->patch(route('university.update', $university), $payload)
            ->assertSessionHasErrors('university_type_other');

        $payload['university_type_other'] = 'Specialised University';
        $this->actingAs($user)->patch(route('university.update', $university), $payload)->assertRedirect();
        $this->assertDatabaseHas('universities', ['id' => $university->id, 'university_type' => 'Specialised University']);
    }

    public function test_update_validates_and_requires_update_permission(): void
    {
        $university = University::query()->firstOrFail();

        $viewer = $this->userWithPermissions(['university.view']);
        $this->actingAs($viewer)->patch(route('university.update', $university), [])->assertForbidden();

        $editor = $this->userWithPermissions(['university.view', 'university.update']);
        $this->actingAs($editor)
            ->from('/admin/university')
            ->patch(route('university.update', $university), ['name' => '', 'code' => 'bad code'])
            ->assertRedirect('/admin/university')
            ->assertSessionHasErrors(['name', 'code', 'country', 'timezone']);
    }

    private function userWithPermissions(array $codes): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'Test role '.uniqid(), 'code' => 'TEST_'.strtoupper(uniqid()), 'status' => 'ACTIVE']);
        $permissionIds = Permission::query()->whereIn('code', $codes)->pluck('id');
        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role->id, ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $user;
    }
}
