<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicDisciplineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_hierarchy_validation_scope_audit_and_permission(): void
    {
        $a = $this->admin();
        $base = ['kind' => 'DISCIPLINE', 'parent_id' => 'none', 'name' => 'Computer Science', 'code' => 'CS', 'display_order' => 1, 'status' => 'ACTIVE'];
        $this->actingAs($a)->post('/admin/disciplines', $base)->assertRedirect();
        $id = (int) \DB::table('academic_disciplines')->value('id');
        $this->actingAs($a)->post('/admin/disciplines', ['kind' => 'SPECIALIZATION', 'parent_id' => $id, 'name' => 'Artificial Intelligence', 'code' => 'AI', 'display_order' => 2, 'status' => 'ACTIVE'])->assertRedirect();
        $this->actingAs($a)->post('/admin/disciplines', ['kind' => 'SPECIALIZATION', 'parent_id' => 'none', 'name' => 'Invalid', 'code' => 'INV', 'display_order' => 3, 'status' => 'ACTIVE'])->assertSessionHasErrors('parent_id');
        $this->assertDatabaseHas('academic_disciplines', ['code' => 'AI', 'parent_id' => $id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'DISCIPLINE_CREATED', 'resource_type' => 'AcademicDiscipline']);
        $this->actingAs(User::factory()->create())->get('/admin/disciplines')->assertForbidden();
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->roles()->attach(Role::whereCode('SUPER_ADMIN')->value('id'), ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $u;
    }
}
