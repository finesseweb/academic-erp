<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $indexName): bool
    {
        return collect(DB::select('SHOW INDEX FROM `college_payment_gateways`'))
            ->contains(fn ($index) => ($index->Key_name ?? null) === $indexName);
    }

    public function up(): void
    {
        // MySQL may use the composite unique index (college_id, provider) to
        // support the FK on college_id. Give that FK its own index first,
        // otherwise MySQL error 1553 prevents the unique index from dropping.
        if (! $this->indexExists('cpg_college_fk_idx')) {
            Schema::table('college_payment_gateways', function (Blueprint $table) {
                $table->index('college_id', 'cpg_college_fk_idx');
            });
        }

        if ($this->indexExists('cpg_college_provider_uq')) {
            Schema::table('college_payment_gateways', function (Blueprint $table) {
                $table->dropUnique('cpg_college_provider_uq');
            });
        }

        if (! $this->indexExists('cpg_profile_lookup_idx')) {
            Schema::table('college_payment_gateways', function (Blueprint $table) {
                $table->index(['college_id', 'provider', 'environment'], 'cpg_profile_lookup_idx');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('cpg_profile_lookup_idx')) {
            Schema::table('college_payment_gateways', function (Blueprint $table) {
                $table->dropIndex('cpg_profile_lookup_idx');
            });
        }

        if (! $this->indexExists('cpg_college_provider_uq')) {
            Schema::table('college_payment_gateways', function (Blueprint $table) {
                $table->unique(['college_id', 'provider'], 'cpg_college_provider_uq');
            });
        }

        // The restored unique index starts with college_id and can again support
        // the college FK, so the temporary dedicated FK-support index can go.
        if ($this->indexExists('cpg_college_fk_idx')) {
            Schema::table('college_payment_gateways', function (Blueprint $table) {
                $table->dropIndex('cpg_college_fk_idx');
            });
        }
    }
};
