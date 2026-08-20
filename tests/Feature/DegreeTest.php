<?php

namespace Tests\Feature;

use App\Models\DegreeLevel;
use App\Models\Role;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DegreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_degree_is_level_owned_validated_audited_and_authorized(): void
    {
        $admin = $this->admin();
        $university = University::firstOrFail();
        $level = DegreeLevel::create(['university_id' => $university->id, 'name' => 'Undergraduate', 'code' => 'UG', 'display_order' => 1, 'status' => 'ACTIVE']);
        $payload = ['degree_level_id' => $level->id, 'name' => 'Bachelor of Science', 'code' => 'BSC', 'description' => 'Science award', 'typical_duration_years' => 3, 'display_order' => 1, 'status' => 'ACTIVE'];
        $this->actingAs($admin)->post('/admin/degrees', $payload)->assertRedirect();
        $id = (int) \DB::table('degrees')->value('id');
        $this->assertDatabaseHas('degrees', ['id' => $id, 'degree_level_id' => $level->id, 'code' => 'BSC']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'DEGREE_CREATED', 'resource_id' => $id]);
        $this->actingAs($admin)->post('/admin/degrees', $payload)->assertSessionHasErrors('code');
        $this->actingAs(User::factory()->create())->get('/admin/degrees')->assertForbidden();
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->roles()->attach(Role::whereCode('SUPER_ADMIN')->value('id'), ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $u;
    }
}
