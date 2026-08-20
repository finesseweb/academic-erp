<?php

namespace App\Http\Controllers;

use App\Models\CourseCategory;
use App\Models\University;
use App\Services\AcademicMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseCategoryController extends Controller
{
    public function __construct(private AcademicMasterService $service) {}

    public function index(Request $r): Response
    {
        $this->auth($r, 'view');
        $u = University::firstOrFail();

        return Inertia::render('academic-masters/course-categories', ['categories' => CourseCategory::where('university_id', $u->id)->orderBy('display_order')->orderBy('name')->get(), 'can' => $this->can($r)]);
    }

    public function store(Request $r): RedirectResponse
    {
        $this->auth($r, 'create');
        $u = University::firstOrFail();
        $this->service->create(CourseCategory::class, $r->validate($this->rules($u->id)), $u->id, $r->user()->id, $r->ip(), 'CourseCategory', 'COURSE_CATEGORY');

        return back()->with('success', 'Course category created.');
    }

    public function update(Request $r, CourseCategory $courseCategory): RedirectResponse
    {
        $this->auth($r, 'update');
        $this->owned($courseCategory);
        $this->service->update($courseCategory, $r->validate($this->rules($courseCategory->university_id, $courseCategory)), $r->user()->id, $r->ip(), 'CourseCategory', 'COURSE_CATEGORY');

        return back()->with('success', 'Course category updated.');
    }

    public function status(Request $r, CourseCategory $courseCategory): RedirectResponse
    {
        $this->auth($r, 'disable');
        $this->owned($courseCategory);
        $s = $r->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]])['status'];
        $this->service->status($courseCategory, $s, $r->user()->id, $r->ip(), 'CourseCategory', 'COURSE_CATEGORY');

        return back()->with('success', 'Course category status updated.');
    }

    private function rules(int $uid, ?CourseCategory $x = null): array
    {
        return ['name' => ['required', 'string', 'max:120'], 'code' => ['required', 'string', 'max:40', Rule::unique('course_categories')->where('university_id', $uid)->ignore($x)], 'category_group' => ['required', Rule::in(['CORE', 'ELECTIVE', 'REQUIREMENT', 'OTHER'])], 'description' => ['nullable', 'string', 'max:1000'], 'display_order' => ['required', 'integer', 'min:0', 'max:65535'], 'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]];
    }

    private function auth(Request $r, string $a): void
    {
        abort_unless($r->user()->hasPermission("course_category.$a"), 403);
    }

    private function owned(CourseCategory $x): void
    {
        abort_unless($x->university_id === University::firstOrFail()->id, 404);
    }

    private function can(Request $r): array
    {
        return ['create' => $r->user()->hasPermission('course_category.create'), 'update' => $r->user()->hasPermission('course_category.update'), 'disable' => $r->user()->hasPermission('course_category.disable')];
    }
}
