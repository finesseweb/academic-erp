<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_course_category_lifecycle_validation_audit_and_authorization(): void
    {
        $a = $this->admin();
        $p = ['name' => 'Major Core', 'code' => 'MC', 'category_group' => 'CORE', 'display_order' => 1, 'status' => 'ACTIVE'];
        $this->actingAs($a)->post('/admin/course-categories', $p)->assertRedirect();
        $id = (int) \DB::table('course_categories')->value('id');
        $this->actingAs($a)->patch("/admin/course-categories/$id/status", ['status' => 'INACTIVE'])->assertRedirect();
        $this->assertDatabaseHas('course_categories', ['id' => $id, 'status' => 'INACTIVE']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'COURSE_CATEGORY_STATUS_CHANGED']);
        $this->actingAs($a)->post('/admin/course-categories', $p)->assertSessionHasErrors('code');
        $this->actingAs(User::factory()->create())->get('/admin/course-categories')->assertForbidden();
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->roles()->attach(Role::whereCode('SUPER_ADMIN')->value('id'), ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'status' => 'ACTIVE']);

        return $u;
    }
}
