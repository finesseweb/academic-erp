<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_demands', function (Blueprint $table) {
            $table->enum('generation_mode', ['ADMISSION_AUTO', 'MANUAL_RECOVERY', 'BULK_PERIOD'])
                ->default('MANUAL_RECOVERY')
                ->after('status');
            $table->index(['college_id', 'generation_mode'], 'fd_col_genmode_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fee_demands', function (Blueprint $table) {
            $table->dropIndex('fd_col_genmode_idx');
            $table->dropColumn('generation_mode');
        });
    }
};
