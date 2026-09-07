<?php

namespace App\Services;

use App\Models\College;
use App\Models\CollegeProgramOffering;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CollegeProgramOfferingService
{
    public function create(
        College $college,
        array $data,
        int $actorId,
        ?string $ip
    ): CollegeProgramOffering {
        $this->assertCollegeActive($college);
        $this->validateAcademicReferences($college, $data);
        $this->assertUnique($college, $data);

        return DB::transaction(function () use ($college, $data, $actorId, $ip) {
            $offering = CollegeProgramOffering::create([
                ...$data,
                'college_id' => $college->id,
                'status' => 'INACTIVE',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->audit(
                'COLLEGE_PROGRAM_OFFERING_CREATED',
                $offering,
                $college,
                $actorId,
                $ip,
                null,
                $offering->toArray()
            );

            return $offering;
        });
    }

    public function update(
        CollegeProgramOffering $offering,
        College $college,
        array $data,
        int $actorId,
        ?string $ip
    ): CollegeProgramOffering {
        $this->assertOwned($offering, $college);
        $this->assertCollegeActive($college);
        $this->validateAcademicReferences($college, $data);
        $this->assertUnique($college, $data, $offering->id);

        if (
            Schema::hasTable('batches')
            && DB::table('batches')->where('college_program_offering_id', $offering->id)->exists()
            && (
                (int) $offering->program_template_id !== (int) $data['program_template_id']
                || (int) $offering->curriculum_id !== (int) $data['curriculum_id']
                || (int) $offering->academic_session_id !== (int) $data['academic_session_id']
            )
        ) {
            throw ValidationException::withMessages([
                'program_template_id' => 'This Program Offering already has Batch records. Its Program, Curriculum and Academic Session are now operationally locked.',
            ]);
        }

        return DB::transaction(function () use ($offering, $college, $data, $actorId, $ip) {
            $before = $offering->toArray();
            $offering->update([...$data, 'updated_by' => $actorId]);

            $this->audit(
                'COLLEGE_PROGRAM_OFFERING_UPDATED',
                $offering,
                $college,
                $actorId,
                $ip,
                $before,
                $offering->fresh()->toArray()
            );

            return $offering;
        });
    }

    public function changeStatus(
        CollegeProgramOffering $offering,
        College $college,
        string $status,
        int $actorId,
        ?string $ip
    ): void {
        $this->assertOwned($offering, $college);
        $this->assertCollegeActive($college);

        if ($status === 'ACTIVE') {
            $this->validateAcademicReferences($college, [
                'program_template_id' => $offering->program_template_id,
                'curriculum_id' => $offering->curriculum_id,
                'academic_session_id' => $offering->academic_session_id,
            ]);
        }

        if (
            $status === 'INACTIVE'
            && Schema::hasTable('batches')
            && DB::table('batches')
                ->where('college_program_offering_id', $offering->id)
                ->where('status', 'ACTIVE')
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'status' => 'Cannot deactivate this Program Offering because an ACTIVE Batch depends on it. Deactivate the Batch first.',
            ]);
        }

        DB::transaction(function () use ($offering, $college, $status, $actorId, $ip) {
            $before = ['status' => $offering->status];
            $offering->update(['status' => $status, 'updated_by' => $actorId]);

            $this->audit(
                $status === 'ACTIVE'
                    ? 'COLLEGE_PROGRAM_OFFERING_ACTIVATED'
                    : 'COLLEGE_PROGRAM_OFFERING_DEACTIVATED',
                $offering,
                $college,
                $actorId,
                $ip,
                $before,
                ['status' => $status]
            );
        });
    }

    private function validateAcademicReferences(College $college, array $data): void
    {
        $session = DB::table('academic_sessions')
            ->where('id', $data['academic_session_id'])
            ->where('university_id', $college->university_id)
            ->whereIn('status', ['PLANNED', 'ACTIVE'])
            ->first();

        if (! $session) {
            throw ValidationException::withMessages([
                'academic_session_id' => 'Select a PLANNED or ACTIVE Academic Session from this University.',
            ]);
        }

        $program = DB::table('program_templates')
            ->where('id', $data['program_template_id'])
            ->where('university_id', $college->university_id)
            ->where('status', 'ACTIVE')
            ->first();

        if (! $program) {
            throw ValidationException::withMessages([
                'program_template_id' => 'Select an active Program Template from this University.',
            ]);
        }

        $curriculum = DB::table('curricula as c')
            ->where('c.id', $data['curriculum_id'])
            ->where(
                'c.university_id',
                $college->university_id
            )
            ->where(
                'c.program_template_id',
                $data['program_template_id']
            )
            ->where(
                'c.academic_session_id',
                $data['academic_session_id']
            )
            ->where('c.lifecycle_status', 'ACTIVE')
            ->where('c.approval_status', 'APPROVED')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('curricula as child')
                    ->whereColumn(
                        'child.parent_curriculum_id',
                        'c.id'
                    )
                    ->where('child.approval_status', 'APPROVED');
            })
            ->first();

        if (! $curriculum) {
            throw ValidationException::withMessages([
                'curriculum_id' =>
                    'Select the current approved ACTIVE Curriculum matching the selected Program Template and Academic Session.',
            ]);
        }
    }

    private function assertUnique(
        College $college,
        array $data,
        ?int $ignoreId = null
    ): void {
        $query = CollegeProgramOffering::query()
            ->where('college_id', $college->id)
            ->where('program_template_id', $data['program_template_id'])
            ->where('academic_session_id', $data['academic_session_id']);

        if ($ignoreId) {
            $query->where('id', '<>', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'program_template_id' => 'This College already has an offering for the selected Program Template and Academic Session.',
            ]);
        }
    }

    private function assertCollegeActive(College $college): void
    {
        if ($college->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'college' => 'Academic setup cannot be changed while this College is inactive.',
            ]);
        }
    }

    private function assertOwned(CollegeProgramOffering $offering, College $college): void
    {
        abort_unless((int) $offering->college_id === (int) $college->id, 404);
    }

    private function audit(
        string $event,
        CollegeProgramOffering $offering,
        College $college,
        int $actorId,
        ?string $ip,
        ?array $before,
        ?array $after
    ): void {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId,
            'event' => $event,
            'resource_type' => 'CollegeProgramOffering',
            'resource_id' => $offering->id,
            'scope_type' => 'COLLEGE',
            'scope_reference' => 'college:'.$college->id,
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
