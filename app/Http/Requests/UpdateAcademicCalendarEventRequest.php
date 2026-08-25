<?php

namespace App\Http\Requests;

use App\Http\Requests\StoreAcademicCalendarEventRequest;

class UpdateAcademicCalendarEventRequest extends StoreAcademicCalendarEventRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('academic_calendar.event_update') ?? false;
    }
}
