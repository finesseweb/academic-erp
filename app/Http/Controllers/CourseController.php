<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseType;
use App\Models\University;
use App\Services\AcademicMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function __construct(private AcademicMasterService $service) {}

    public function index(Request $r): Response
    {
        $this->authorize($r, 'view');
        $u = University::firstOrFail();

        return Inertia::render('academic-masters/courses', [
            'courses' => Course::with([
                    'category:id,name',
                    'type:id,name',
                ])
                ->where('university_id', $u->id)
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(),

            'categories' => CourseCategory::where('university_id', $u->id)
                ->where('status', 'ACTIVE')
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(['id', 'name']),

            'types' => CourseType::where('university_id', $u->id)
                ->where('status', 'ACTIVE')
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(['id', 'name']),

            'can' => $this->can($r),
        ]);
    }

    public function store(Request $r): RedirectResponse
    {
        $this->authorize($r, 'create');
        $u = University::firstOrFail();

        $this->service->create(
            Course::class,
            $this->validated($r, $u->id),
            $u->id,
            $r->user()->id,
            $r->ip(),
            'Course',
            'COURSE'
        );

        return back()->with('success', 'Course / Subject created.');
    }

    public function update(Request $r, Course $course): RedirectResponse
    {
        $this->authorize($r, 'update');
        $this->owned($course);

        $this->service->update(
            $course,
            $this->validated($r, $course->university_id, $course),
            $r->user()->id,
            $r->ip(),
            'Course',
            'COURSE'
        );

        return back()->with('success', 'Course / Subject updated.');
    }

    public function updateStatus(Request $r, Course $course): RedirectResponse
    {
        $this->authorize($r, 'disable');
        $this->owned($course);

        $status = $r->validate([
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ])['status'];

        $this->service->status(
            $course,
            $status,
            $r->user()->id,
            $r->ip(),
            'Course',
            'COURSE'
        );

        return back()->with('success', 'Course / Subject status updated.');
    }

    private function validated(Request $r, int $uid, ?Course $record = null): array
    {
        $data = $r->validate([
            'course_category_id' => [
                'required',
                Rule::exists('course_categories', 'id')->where(
                    fn ($q) => $q
                        ->where('university_id', $uid)
                        ->where('status', 'ACTIVE')
                ),
            ],

            'course_type_id' => [
                'required',
                Rule::exists('course_types', 'id')->where(
                    fn ($q) => $q
                        ->where('university_id', $uid)
                        ->where('status', 'ACTIVE')
                ),
            ],

            'name' => ['required', 'string', 'max:150'],

            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('courses', 'code')
                    ->where(fn ($q) => $q->where('university_id', $uid))
                    ->ignore($record),
            ],

            'description' => ['nullable', 'string', 'max:1000'],

            'display_order' => [
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],

            'status' => [
                'required',
                Rule::in(['ACTIVE', 'INACTIVE']),
            ],
        ]);

        $data['code'] = strtoupper(trim($data['code']));

        return $data;
    }

    private function authorize(Request $r, string $action): void
    {
        abort_unless(
            $r->user()->hasPermission("course.$action"),
            403
        );
    }

    private function owned(Course $course): void
    {
        abort_unless(
            $course->university_id === University::firstOrFail()->id,
            404
        );
    }

    private function can(Request $r): array
    {
        return [
            'create' => $r->user()->hasPermission('course.create'),
            'update' => $r->user()->hasPermission('course.update'),
            'disable' => $r->user()->hasPermission('course.disable'),
        ];
    }
}