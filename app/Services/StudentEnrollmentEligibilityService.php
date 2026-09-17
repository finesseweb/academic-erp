<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\College;
use App\Models\StudentEnrollment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StudentEnrollmentEligibilityService
{
    public function __construct(private readonly FeeClearanceService $feeClearance) {}

    public function queue(College $college, array $filters): LengthAwarePaginator
    {
        $sessionId = (int) ($filters['session_id'] ?? 0);
        $offeringId = (int) ($filters['offering_id'] ?? 0);
        $search = trim((string) ($filters['q'] ?? ''));
        $status = strtoupper(trim((string) ($filters['status'] ?? '')));
        $perPage = (int) ($filters['per_page'] ?? 25);
        if (! in_array($perPage, [25, 50, 100], true)) $perPage = 25;

        $query = Admission::query()
            ->with([
                'application:id,candidate_name,application_no,email,phone',
                'application.academicPreference.discipline:id,name,code',
                'intake.offering.programTemplate:id,name,code',
                'intake.offering.academicSession:id,name,code',
            ])
            ->where('college_id', $college->id)
            ->where('status', 'CONFIRMED')
            ->whereHas('intake.offering', function ($q) use ($sessionId, $offeringId) {
                if ($sessionId > 0) $q->where('academic_session_id', $sessionId);
                if ($offeringId > 0) $q->whereKey($offeringId);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('admission_no', 'like', '%'.$search.'%')
                        ->orWhereHas('application', fn ($a) => $a
                            ->where('candidate_name', 'like', '%'.$search.'%')
                            ->orWhere('application_no', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('confirmed_at')->orderByDesc('id');

        $map = fn (Admission $admission) => $this->row($college, $admission);

        if (in_array($status, ['READY', 'BLOCKED', 'ENROLLED'], true)) {
            $all = $query->get()->map($map)->where('enrollment_status', $status)->values();
            $page = max((int) request()->query('page', 1), 1);
            return new \Illuminate\Pagination\LengthAwarePaginator(
                $all->forPage($page, $perPage)->values(), $all->count(), $perPage, $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $page = $query->paginate($perPage)->withQueryString();
        $page->setCollection($page->getCollection()->map($map));
        return $page;
    }

    private function row(College $college, Admission $admission): array
    {
        $offering = $admission->intake?->offering;
        $sessionId = (int) ($offering?->academic_session_id ?? 0);
        $offeringId = (int) ($offering?->id ?? 0);
        $clearance = $this->feeClearance->forAdmission($college, $admission, $sessionId, $offeringId);
        $enrollment = StudentEnrollment::query()
            ->where('college_id', $college->id)
            ->where('admission_id', $admission->id)
            ->first(['id', 'student_id', 'status', 'enrolled_at']);

        $enrolled = $enrollment?->status === 'ENROLLED';
        $ready = ! $enrolled && (bool) $clearance['is_cleared'];

        return [
            'admission_id' => (int) $admission->id,
            'admission_no' => $admission->admission_no,
            'application_no' => $admission->application?->application_no,
            'candidate_name' => $admission->application?->candidate_name,
            'email' => $admission->application?->email,
            'phone' => $admission->application?->phone,
            'programme' => $offering?->programTemplate?->name,
            'programme_code' => $offering?->programTemplate?->code,
            'discipline' => $admission->application?->academicPreference?->discipline?->name,
            'discipline_code' => $admission->application?->academicPreference?->discipline?->code,
            'session' => $offering?->academicSession?->name,
            'offering_id' => $offeringId,
            'fee_clearance_status' => $clearance['status'],
            'fee_clearance_outstanding' => $clearance['clearance_outstanding'],
            'currency' => $clearance['currency'],
            'enrollment_status' => $enrolled ? 'ENROLLED' : ($ready ? 'READY' : 'BLOCKED'),
            'block_reason' => $enrolled ? null : ($ready ? null : 'Required Fee Clearance is pending.'),
            'enrollment_id' => $enrollment?->id,
            'student_id' => $enrollment?->student_id,
            'enrolled_at' => optional($enrollment?->enrolled_at)->toIso8601String(),
        ];
    }
}
