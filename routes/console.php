<?php

use App\Models\College;
use App\Services\FeeLateFineService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('fees:recalculate-late-fines {--date=}', function (FeeLateFineService $service) {
    $asOf = $this->option('date') ? Carbon::parse($this->option('date')) : now();
    $collegeIds = DB::table('fee_late_fine_rules')->where('status','ACTIVE')->distinct()->pluck('college_id');
    $totals = ['created'=>0,'superseded'=>0,'reversed'=>0,'unchanged'=>0];
    foreach (College::whereIn('id',$collegeIds)->get() as $college) {
        $result = $service->recalculateCollege($college,$asOf,null);
        foreach ($totals as $key=>$value) $totals[$key] += $result[$key] ?? 0;
    }
    $this->info("Late fine recalculation complete for {$asOf->toDateString()}: {$totals['created']} posted/recalculated, {$totals['reversed']} reversed, {$totals['unchanged']} unchanged.");
})->purpose('Calculate/recalculate active installment late fine rules for all colleges');

Schedule::command('fees:recalculate-late-fines')->dailyAt('00:10')->withoutOverlapping();

Artisan::command('students:normalize-enrollment-academics {--apply : Persist READY normalizations} {--college= : Limit to one College ID} {--enrollment= : Limit to one Enrollment ID}', function (\App\Services\StudentEnrollmentAcademicNormalizationService $service) {
    $apply = (bool) $this->option('apply');
    $collegeId = $this->option('college') ? (int) $this->option('college') : null;
    $enrollmentId = $this->option('enrollment') ? (int) $this->option('enrollment') : null;
    $report = $service->scan($collegeId, $enrollmentId, $apply);

    $this->info(($apply ? 'APPLY' : 'DRY RUN').' — Enrollment Academic Normalization');
    $this->table(['Scanned','Already canonical','Ready','Normalized','Needs review'], [[
        $report['scanned'], $report['already_canonical'], $report['ready'], $report['normalized'], $report['needs_review'],
    ]]);
    foreach ($report['rows'] as $row) {
        if (($row['status'] ?? '') === 'NEEDS_REVIEW') {
            $this->warn('Enrollment '.$row['enrollment_id'].': '.implode(' ', $row['issues'] ?? []));
        }
    }
    if (! $apply && $report['ready'] > 0) $this->comment('Dry run only. Re-run with --apply after reviewing READY/NEEDS_REVIEW counts.');
    return $report['needs_review'] > 0 ? 2 : 0;
})->purpose('Normalize legacy ADMISSION enrollments into the canonical Enrollment academic context used by Import and downstream modules');
