<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUniversityRequest;
use App\Models\University;
use App\Services\UniversityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UniversityController extends Controller
{
    public function show(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('university.view'), 403);
        $university = University::query()->firstOrFail();
        $universityData = $university->toArray();
        $universityData['established_on'] = $university->established_on?->toDateString();

        return Inertia::render('university/profile', [
            'university' => $universityData,
            'can' => ['update' => $request->user()->hasPermission('university.update')],
        ]);
    }

    public function update(UpdateUniversityRequest $request, University $university, UniversityService $service): RedirectResponse
    {
        $data = $request->safe()->except('university_type_other');
        if (($data['university_type'] ?? null) === 'Other') {
            $data['university_type'] = $request->validated('university_type_other');
        }

        $service->update($university, $data, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'University profile updated.']);
    }
}
