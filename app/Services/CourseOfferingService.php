<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\College;
use App\Models\CourseOffering;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseOfferingService
{
    /** @return Collection<int,CourseOffering> */
    public function createForDisciplineTerm(College $college, array $data, int $actorId, ?string $ip): Collection
    {
        $this->assertCollegeActive($college);
        $batch = $this->validateBatch($college, (int) $data['batch_id'], true);
        $curriculumId = (int) $batch->offering->curriculum_id;
        $disciplineId = (int) $data['discipline_id'];
        $termId = (int) $data['term_id'];

        $disciplineAllowed = DB::table('curriculum_course_mappings as m')
            ->join('curriculum_slots as s', 's.id', '=', 'm.curriculum_slot_id')
            ->join('curriculum_terms as t', 't.id', '=', 's.curriculum_term_id')
            ->where('t.curriculum_id', $curriculumId)->where('m.discipline_id', $disciplineId)->exists();
        if (! $disciplineAllowed) throw ValidationException::withMessages(['discipline_id' => 'Select a Discipline defined by the University Curriculum linked to this Batch.']);

        $termAllowed = DB::table('curriculum_terms')->where('id', $termId)->where('curriculum_id', $curriculumId)->where('status', 'ACTIVE')->exists();
        if (! $termAllowed) throw ValidationException::withMessages(['term_id' => 'Select an ACTIVE Term from the Curriculum linked to this Batch.']);

        // Course delivery planning must not depend on Student Enrollment. The University Curriculum is authoritative:
        // mandatory mappings are always included; active CHOICE mappings are exposed for the College to select for delivery.
        $applicableMappings = DB::table('curriculum_course_mappings as m')
            ->join('curriculum_slots as s', 's.id', '=', 'm.curriculum_slot_id')
            ->where('s.curriculum_term_id', $termId)
            ->where('s.status', 'ACTIVE')->where('m.status', 'ACTIVE')
            ->where(fn ($q) => $q->whereNull('m.discipline_id')->orWhere('m.discipline_id', $disciplineId))
            ->orderBy('s.display_order')->orderBy('m.display_order')
            ->get(['m.id', 's.selection_mode']);

        if ($applicableMappings->isEmpty()) throw ValidationException::withMessages(['discipline_id' => 'No active Curriculum courses are applicable to this Discipline and Term.']);

        $mandatoryIds = $applicableMappings
            ->filter(fn ($mapping) => strtoupper((string) $mapping->selection_mode) === 'MANDATORY')
            ->pluck('id');
        $allowedChoiceIds = $applicableMappings
            ->reject(fn ($mapping) => strtoupper((string) $mapping->selection_mode) === 'MANDATORY')
            ->pluck('id');
        $selectedChoiceIds = collect($data['selected_choice_mapping_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

        $invalidChoiceIds = $selectedChoiceIds->diff($allowedChoiceIds);
        if ($invalidChoiceIds->isNotEmpty()) {
            throw ValidationException::withMessages(['selected_choice_mapping_ids' => 'One or more selected Choice courses are not active/applicable to this Batch, Discipline and Term.']);
        }

        $mappingIds = $mandatoryIds->merge($selectedChoiceIds)->unique()->values();
        if ($mappingIds->isEmpty()) throw ValidationException::withMessages(['selected_choice_mapping_ids' => 'Select at least one Choice course for this Discipline and Term.']);

        $existing = CourseOffering::where('batch_id', $batch->id)->whereIn('curriculum_course_mapping_id', $mappingIds)->pluck('curriculum_course_mapping_id');
        $newIds = $mappingIds->diff($existing);
        if ($newIds->isEmpty()) throw ValidationException::withMessages(['term_id' => 'All applicable courses for this Discipline and Term are already configured for this Batch.']);

        return DB::transaction(function () use ($newIds, $batch, $data, $college, $actorId, $ip, $disciplineId, $termId) {
            return $newIds->map(function ($mappingId) use ($batch, $data, $college, $actorId, $ip, $disciplineId, $termId) {
                $offering = CourseOffering::create([
                    'batch_id' => $batch->id,
                    'curriculum_course_mapping_id' => $mappingId,
                    'status' => 'INACTIVE',
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
                $this->audit('COURSE_OFFERING_CREATED', $offering, $college, $actorId, $ip, null, array_merge($offering->toArray(), ['discipline_id' => $disciplineId, 'term_id' => $termId, 'creation_mode' => 'DISCIPLINE_TERM_BULK']));
                return $offering;
            });
        });
    }

    public function changeStatus(CourseOffering $offering, College $college, string $status, int $actorId, ?string $ip): void
    {
        $batch = $this->validateBatch($college, (int) $offering->batch_id, $status === 'ACTIVE');
        $this->validateMapping($batch, (int) $offering->curriculum_course_mapping_id, $status === 'ACTIVE');
        $this->assertCollegeActive($college);
        if ($offering->status === $status) return;
        DB::transaction(function () use ($offering, $college, $status, $actorId, $ip) {
            $before = ['status' => $offering->status];
            $offering->update(['status' => $status, 'updated_by' => $actorId]);
            $this->audit($status === 'ACTIVE' ? 'COURSE_OFFERING_ACTIVATED' : 'COURSE_OFFERING_DEACTIVATED', $offering, $college, $actorId, $ip, $before, ['status' => $status]);
        });
    }

    private function validateBatch(College $college, int $batchId, bool $requireActive): Batch
    {
        $batch = Batch::query()->with(['offering'])->whereKey($batchId)->whereHas('offering', fn ($q) => $q->where('college_id', $college->id))->first();
        if (! $batch) throw ValidationException::withMessages(['batch_id' => 'Select a Batch belonging to this College.']);
        if ($requireActive && ($batch->status !== 'ACTIVE' || $batch->offering?->status !== 'ACTIVE')) throw ValidationException::withMessages(['batch_id' => 'Course delivery requires an ACTIVE Batch under an ACTIVE Program Offering.']);
        return $batch;
    }

    private function validateMapping(Batch $batch, int $mappingId, bool $requireActive): \App\Models\CurriculumCourseMapping
    {
        $mapping = \App\Models\CurriculumCourseMapping::query()->whereKey($mappingId)->whereHas('slot.term', fn ($q) => $q->where('curriculum_id', (int) $batch->offering->curriculum_id))->first();
        if (! $mapping) throw ValidationException::withMessages(['course_offering' => 'Course Offering no longer belongs to this Batch Curriculum.']);
        if ($requireActive) {
            $mapping->loadMissing('slot.term');
            if ($mapping->status !== 'ACTIVE' || $mapping->slot?->status !== 'ACTIVE' || $mapping->slot?->term?->status !== 'ACTIVE') throw ValidationException::withMessages(['course_offering' => 'Only an ACTIVE Curriculum Term, Slot and Course Mapping can be activated for delivery.']);
        }
        return $mapping;
    }

    private function assertCollegeActive(College $college): void
    {
        if ($college->status !== 'ACTIVE') throw ValidationException::withMessages(['college' => 'Course Offerings cannot be changed while this College is inactive.']);
    }

    private function audit(string $event, CourseOffering $offering, College $college, int $actorId, ?string $ip, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert(['actor_user_id'=>$actorId,'event'=>$event,'resource_type'=>'CourseOffering','resource_id'=>$offering->id,'scope_type'=>'COLLEGE','scope_reference'=>'college:'.$college->id,'before'=>$before?json_encode($before):null,'after'=>$after?json_encode($after):null,'ip_address'=>$ip,'created_at'=>now()]);
    }
}
