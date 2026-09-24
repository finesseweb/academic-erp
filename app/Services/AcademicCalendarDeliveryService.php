<?php

namespace App\Services;

use App\Models\AcademicCalendarEvent;
use App\Models\AcademicCalendarTermPeriod;
use App\Models\College;
use App\Models\CollegeAcademicCalendar;
use App\Models\CollegeCalendarOverride;
use App\Models\FacultyAllocation;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class AcademicCalendarDeliveryService
{
    public function assertClassDateAllowed(College $college, FacultyAllocation $allocation, CarbonInterface $date): void
    {
        $allocation->loadMissing([
            'courseOffering.batch.offering',
            'courseOffering.curriculumCourseMapping.slot.term',
        ]);

        $offering = $allocation->courseOffering->batch->offering;
        $term = $allocation->courseOffering->curriculumCourseMapping->slot->term;
        $collegeCalendar = CollegeAcademicCalendar::query()
            ->with('universityCalendar')
            ->where('college_id', $college->id)
            ->where('status', 'ACTIVE')
            ->whereHas('universityCalendar', fn ($query) => $query
                ->where('status', 'ACTIVE')
                ->where('academic_session_id', $offering->academic_session_id))
            ->first();

        if (! $collegeCalendar) {
            throw ValidationException::withMessages([
                'class_date' => 'Activate the College Academic Calendar for this Programme Offering Academic Session before scheduling classes.',
            ]);
        }

        $termPeriod = AcademicCalendarTermPeriod::query()
            ->where('academic_calendar_id', $collegeCalendar->university_academic_calendar_id)
            ->where('curriculum_term_id', $term->id)
            ->where('status', 'ACTIVE')
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->first();

        if (! $termPeriod) {
            throw ValidationException::withMessages([
                'class_date' => "The selected date is outside the active Academic Calendar period for {$term->name}.",
            ]);
        }

        $events = AcademicCalendarEvent::query()
            ->where('academic_calendar_id', $collegeCalendar->university_academic_calendar_id)
            ->where('status', 'ACTIVE')
            ->whereIn('event_type', ['HOLIDAY', 'VACATION'])
            ->get(['id', 'event_type', 'title', 'start_date', 'end_date']);
        $overrides = CollegeCalendarOverride::query()
            ->where('college_academic_calendar_id', $collegeCalendar->id)
            ->where('status', 'ACTIVE')
            ->whereIn('academic_calendar_event_id', $events->pluck('id'))
            ->get(['academic_calendar_event_id', 'title', 'start_date', 'end_date'])
            ->keyBy('academic_calendar_event_id');

        foreach ($events as $event) {
            $effective = $overrides->get($event->id) ?? $event;
            if ($date->betweenIncluded($effective->start_date, $effective->end_date)) {
                throw ValidationException::withMessages([
                    'class_date' => "Classes cannot be scheduled on {$event->event_type}: {$effective->title}. Use the governed Academic Calendar override workflow to change the effective dates.",
                ]);
            }
        }
    }
}
