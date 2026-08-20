<?php

namespace Tests\Feature;

use App\Models\AcademicDiscipline;
use App\Models\Degree;
use App\Models\DegreeLevel;
use App\Models\Role;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_template_validates_parents_and_audits(): void
    {
        $a = $this->admin();
        $u = University::firstOrFail();
        $l = DegreeLevel::create(['university_id' => $u->id, 'name' => 'UG', 'code' => 'UG', 'display_order' => 1, 'status' => 'ACTIVE']);
        $d = Degree::create(['university_id' => $u->id, 'degree_level_id' => $l->id, 'name' => 'BSc', 'code' => 'BSC', 'display_order' => 1, 'status' => 'ACTIVE']);
        $s = AcademicDiscipline::create(['university_id' => $u->id, 'kind' => 'DISCIPLINE', 'name' => 'Science', 'code' => 'SCI', 'display_order' => 1, 'status' => 'ACTIVE']);
        $p = ['degree_id' => $d->id, 'discipline_id' => $s->id, 'name' => 'BSc Science', 'code' => 'BSC-SCI', 'term_structure' => 'SEMESTER', 'duration_terms' => 6, 'display_order' => 1, 'status' => 'ACTIVE'];
        $this->actingAs($a)->post('/admin/program-templates', $p)->assertRedirect();
        $this->assertDatabaseHas('program_templates', ['code' => 'BSC-SCI', 'duration_terms' => 6]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'PROGRAM_TEMPLATE_CREATED']);
        $this->actingAs(User::factory()->create())->get('/admin/program-templates')->assertForbidden();
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->roles()->attach(Role::whereCode('SUPER_ADMIN')->value('id'), ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $u;
    }
}
