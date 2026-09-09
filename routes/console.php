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
