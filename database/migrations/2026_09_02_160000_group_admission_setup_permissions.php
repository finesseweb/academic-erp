<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $resources = [
        'college_admission_selection_rule',
        'college_admission_cycle',
        'college_admission_form',
        'college_admission_application',
        'college_admission_score',
        'college_admission_interview',
        'college_admission_merit',
    ];

    public function up(): void
    {
        DB::table('permissions')
            ->whereIn('resource', $this->resources)
            ->update([
                'module' => 'Admission Setup',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('resource', [
                'college_admission_cycle',
                'college_admission_form',
                'college_admission_application',
                'college_admission_score',
                'college_admission_interview',
                'college_admission_merit',
            ])
            ->update([
                'module' => 'Admission',
                'updated_at' => now(),
            ]);

        DB::table('permissions')
            ->where('resource', 'college_admission_selection_rule')
            ->update([
                'module' => 'College Academic Setup',
                'updated_at' => now(),
            ]);
    }
};
