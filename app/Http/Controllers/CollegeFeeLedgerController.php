<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\Admission;
use App\Models\College;
use App\Services\FeeLedgerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollegeFeeLedgerController extends Controller
{
    public function index(Request $request, College $college, FeeLedgerService $ledger): Response
    {
        abort_unless($request->user()->hasCollegePermission('college_fee_ledger.view', $college->id), 403);

        $sessions = AcademicSession::query()
            ->where('university_id', $college->university_id)
            ->where('status', 'ACTIVE')
            ->orderByDesc('is_current')->orderByDesc('starts_on')
            ->get(['id','name','code','is_current']);

        $currentSessionId = (int) ($sessions->firstWhere('is_current', true)?->id ?? $sessions->first()?->id ?? 0);
        $sessionId = (int) $request->query('session_id', $currentSessionId);
        $search = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 25);
        if (! in_array($perPage, [25,50,100], true)) $perPage = 25;

        $students = $ledger->studentRegister($college, [
            'session_id' => $sessionId,
            'q' => $search,
            'per_page' => $perPage,
        ]);

        $selected = null;
        $admissionId = (int) $request->query('admission_id', 0);
        if ($admissionId > 0) {
            $admission = Admission::query()->where('college_id', $college->id)->findOrFail($admissionId);
            $selected = $ledger->ledger($college, $admission, $sessionId);
        }

        return Inertia::render('college-fee-ledger/index', [
            'college' => $college->only(['id','name','code']),
            'sessions' => $sessions->map(fn ($session) => [
                'id' => $session->id,
                'name' => $session->name,
                'code' => $session->code,
                'is_current' => (bool) $session->is_current,
            ])->values(),
            'students' => $students,
            'ledger' => $selected,
            'filters' => [
                'session_id' => $sessionId,
                'q' => $search,
                'per_page' => $perPage,
                'admission_id' => $admissionId ?: null,
            ],
        ]);
    }
}
