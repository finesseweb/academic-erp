<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Conservative repair for legacy Course Master rows that were
         * accidentally saved with the subject name in `code` and the
         * compact course code in `name`.
         *
         * Example repaired:
         * name = BA-HIS-101
         * code = HISTORY OF ANCIENT INDIA
         *
         * becomes:
         * name = HISTORY OF ANCIENT INDIA
         * code = BA-HIS-101
         *
         * We only touch rows where:
         * - name is compact (no whitespace),
         * - name contains a hyphen,
         * - code contains whitespace.
         * This avoids blindly swapping valid Course Master records.
         */
        DB::table('courses')
            ->orderBy('id')
            ->chunkById(100, function ($courses) {
                foreach ($courses as $course) {
                    $name = trim((string) $course->name);
                    $code = trim((string) $course->code);

                    $looksReversed =
                        $name !== '' &&
                        $code !== '' &&
                        ! preg_match('/\s/u', $name) &&
                        str_contains($name, '-') &&
                        preg_match('/\s/u', $code);

                    if (! $looksReversed) {
                        continue;
                    }

                    $codeAlreadyExists = DB::table('courses')
                        ->where('university_id', $course->university_id)
                        ->where('id', '<>', $course->id)
                        ->where('code', $name)
                        ->exists();

                    if ($codeAlreadyExists) {
                        continue;
                    }

                    DB::table('courses')
                        ->where('id', $course->id)
                        ->update([
                            'name' => $code,
                            'code' => $name,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Intentionally not reversed automatically: this migration repairs
        // malformed legacy data and rollback must not corrupt valid records.
    }
};
