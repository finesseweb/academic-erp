<?php

namespace App\Http\Controllers;

use App\Models\ReservationCategory;
use App\Models\University;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReservationCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeAction($request, 'view');
        $university = University::query()->firstOrFail();

        return Inertia::render('academic-masters/reservation-categories', [
            'categories' => ReservationCategory::query()
                ->where('university_id', $university->id)
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(),
            'can' => [
                'create' => $request->user()->hasPermission('reservation_category.create'),
                'update' => $request->user()->hasPermission('reservation_category.update'),
                'enable' => $request->user()->hasPermission('reservation_category.enable'),
                'disable' => $request->user()->hasPermission('reservation_category.disable'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAction($request, 'create');
        $university = University::query()->firstOrFail();
        $data = $request->validate($this->rules($university->id));

        $category = ReservationCategory::create([
            ...$data,
            'university_id' => $university->id,
            'status' => 'ACTIVE',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->audit('RESERVATION_CATEGORY_CREATED', $category, $request, null, $category->toArray());

        return back()->with('success', 'Reservation / Quota category created.');
    }

    public function update(
        Request $request,
        ReservationCategory $reservationCategory
    ): RedirectResponse {
        $this->authorizeAction($request, 'update');
        $this->assertOwned($reservationCategory);

        $before = $reservationCategory->toArray();
        $data = $request->validate($this->rules(
            $reservationCategory->university_id,
            $reservationCategory
        ));

        $reservationCategory->update([
            ...$data,
            'updated_by' => $request->user()->id,
        ]);

        $this->audit(
            'RESERVATION_CATEGORY_UPDATED',
            $reservationCategory,
            $request,
            $before,
            $reservationCategory->fresh()->toArray()
        );

        return back()->with('success', 'Reservation / Quota category updated.');
    }

    public function status(
        Request $request,
        ReservationCategory $reservationCategory
    ): RedirectResponse {
        $this->assertOwned($reservationCategory);
        $status = $request->validate([
            'status' => ['required', Rule::in(['ACTIVE','INACTIVE'])],
        ])['status'];

        $this->authorizeAction($request, $status === 'ACTIVE' ? 'enable' : 'disable');

        if (
            $status === 'INACTIVE' &&
            Schema::hasTable('college_program_reservation_allocations') &&
            DB::table('college_program_reservation_allocations')
                ->where('reservation_category_id', $reservationCategory->id)
                ->exists()
        ) {
            return back()->withErrors([
                'reservation_category' =>
                    'This category is already used by a College Reservation plan and cannot be deactivated.',
            ]);
        }

        $before = ['status' => $reservationCategory->status];
        $reservationCategory->update([
            'status' => $status,
            'updated_by' => $request->user()->id,
        ]);

        $this->audit(
            $status === 'ACTIVE'
                ? 'RESERVATION_CATEGORY_ACTIVATED'
                : 'RESERVATION_CATEGORY_DEACTIVATED',
            $reservationCategory,
            $request,
            $before,
            ['status' => $status]
        );

        return back()->with('success', 'Reservation category status updated.');
    }

    private function rules(
        int $universityId,
        ?ReservationCategory $category = null
    ): array {
        return [
            'name' => ['required','string','max:120'],
            'code' => [
                'required','string','max:40',
                Rule::unique('reservation_categories')
                    ->where('university_id', $universityId)
                    ->ignore($category),
            ],
            'nature' => ['required', Rule::in(['VERTICAL','HORIZONTAL'])],
            'description' => ['nullable','string','max:1000'],
            'display_order' => ['required','integer','min:0','max:65535'],
        ];
    }

    private function authorizeAction(Request $request, string $action): void
    {
        abort_unless(
            $request->user()->hasPermission("reservation_category.{$action}"),
            403
        );
    }

    private function assertOwned(ReservationCategory $category): void
    {
        abort_unless(
            (int) $category->university_id ===
                (int) University::query()->firstOrFail()->id,
            404
        );
    }

    private function audit(
        string $event,
        ReservationCategory $category,
        Request $request,
        ?array $before,
        ?array $after
    ): void {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $request->user()->id,
            'event' => $event,
            'resource_type' => 'ReservationCategory',
            'resource_id' => $category->id,
            'scope_type' => 'UNIVERSITY',
            'scope_reference' => 'university',
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
