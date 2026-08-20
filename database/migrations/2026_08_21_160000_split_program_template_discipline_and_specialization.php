<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_templates', function (Blueprint $table) {
            $table->foreignId('specialization_id')
                ->nullable()
                ->after('discipline_id')
                ->constrained('academic_disciplines')
                ->restrictOnDelete();
        });

        DB::table('program_templates')
            ->whereNotNull('discipline_id')
            ->orderBy('id')
            ->each(function (object $template): void {
                $discipline = DB::table('academic_disciplines')
                    ->where('id', $template->discipline_id)
                    ->first(['kind', 'parent_id']);

                if ($discipline?->kind === 'SPECIALIZATION') {
                    DB::table('program_templates')
                        ->where('id', $template->id)
                        ->update([
                            'discipline_id' => $discipline->parent_id,
                            'specialization_id' => $template->discipline_id,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('program_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('specialization_id');
        });
    }
};
