<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_template_disciplines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_template_id');
            $table->unsignedBigInteger('discipline_id');
            $table->timestamps();

            $table->foreign('program_template_id', 'ptd_template_fk')
                ->references('id')
                ->on('program_templates')
                ->cascadeOnDelete();

            $table->foreign('discipline_id', 'ptd_discipline_fk')
                ->references('id')
                ->on('academic_disciplines')
                ->restrictOnDelete();

            $table->unique(
                ['program_template_id', 'discipline_id'],
                'program_tpl_discipline_unique'
            );
            $table->index('discipline_id', 'ptd_discipline_idx');
        });

        Schema::create('program_template_discipline_specializations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_template_discipline_id');
            $table->unsignedBigInteger('specialization_id');
            $table->timestamps();

            $table->foreign('program_template_discipline_id', 'ptds_mapping_fk')
                ->references('id')
                ->on('program_template_disciplines')
                ->cascadeOnDelete();

            $table->foreign('specialization_id', 'ptds_specialization_fk')
                ->references('id')
                ->on('academic_disciplines')
                ->restrictOnDelete();

            $table->unique(
                ['program_template_discipline_id', 'specialization_id'],
                'program_tpl_disc_spec_unique'
            );
            $table->index('specialization_id', 'ptds_specialization_idx');
        });

        // Preserve existing single-discipline assignments before removing
        // the old direct columns.
        if (Schema::hasColumn('program_templates', 'discipline_id')) {
            $hasOldSpecialization = Schema::hasColumn('program_templates', 'specialization_id');

            DB::table('program_templates')
                ->whereNotNull('discipline_id')
                ->orderBy('id')
                ->get()
                ->each(function ($template) use ($hasOldSpecialization) {
                    $mappingId = DB::table('program_template_disciplines')->insertGetId([
                        'program_template_id' => $template->id,
                        'discipline_id' => $template->discipline_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if ($hasOldSpecialization && ! empty($template->specialization_id)) {
                        DB::table('program_template_discipline_specializations')->insert([
                            'program_template_discipline_id' => $mappingId,
                            'specialization_id' => $template->specialization_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });

            Schema::table('program_templates', function (Blueprint $table) {
                $table->dropConstrainedForeignId('discipline_id');
            });
        }

        // Some development copies had a direct specialization_id column.
        if (Schema::hasColumn('program_templates', 'specialization_id')) {
            Schema::table('program_templates', function (Blueprint $table) {
                $table->dropConstrainedForeignId('specialization_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('program_template_discipline_specializations');
        Schema::dropIfExists('program_template_disciplines');

        if (! Schema::hasColumn('program_templates', 'discipline_id')) {
            Schema::table('program_templates', function (Blueprint $table) {
                $table->foreignId('discipline_id')
                    ->nullable()
                    ->constrained('academic_disciplines')
                    ->restrictOnDelete();
            });
        }
    }
};
