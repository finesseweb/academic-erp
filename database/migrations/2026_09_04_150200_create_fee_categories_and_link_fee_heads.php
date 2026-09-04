<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->restrictOnDelete();
            $table->foreignId('college_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('code', 40);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->string('status', 20)->default('INACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['university_id', 'college_id', 'status'], 'fee_categories_owner_status_idx');
            $table->index(['university_id', 'code'], 'fee_categories_university_code_idx');
        });

        Schema::table('fee_heads', function (Blueprint $table) {
            $table->unsignedBigInteger('fee_category_id')->nullable()->after('code');
        });

        $now = now();
        $defaults = [
            ['ADMISSION', 'Admission'],
            ['TUITION', 'Tuition'],
            ['REGISTRATION', 'Registration'],
            ['EXAMINATION', 'Examination'],
            ['LIBRARY', 'Library'],
            ['LAB', 'Lab'],
            ['HOSTEL', 'Hostel'],
            ['TRANSPORT', 'Transport'],
            ['DEVELOPMENT', 'Development'],
            ['CERTIFICATE', 'Certificate'],
            ['OTHER', 'Other'],
        ];

        foreach (DB::table('universities')->pluck('id') as $universityId) {
            foreach ($defaults as $order => [$code, $name]) {
                DB::table('fee_categories')->updateOrInsert(
                    ['university_id' => $universityId, 'college_id' => null, 'code' => $code],
                    [
                        'name' => $name,
                        'description' => null,
                        'display_order' => $order + 1,
                        'status' => 'ACTIVE',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        foreach (DB::table('fee_heads')->select('id', 'university_id', 'category')->get() as $head) {
            $categoryId = DB::table('fee_categories')
                ->where('university_id', $head->university_id)
                ->whereNull('college_id')
                ->where('code', strtoupper((string) $head->category))
                ->value('id');

            if (! $categoryId) {
                $categoryId = DB::table('fee_categories')->insertGetId([
                    'university_id' => $head->university_id,
                    'college_id' => null,
                    'name' => ucwords(strtolower(str_replace('_', ' ', (string) $head->category))),
                    'code' => strtoupper((string) $head->category),
                    'description' => 'Migrated from legacy Fee Head category.',
                    'display_order' => 999,
                    'status' => 'ACTIVE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('fee_heads')->where('id', $head->id)->update(['fee_category_id' => $categoryId]);
        }

        Schema::table('fee_heads', function (Blueprint $table) {
            $table->foreign('fee_category_id', 'fee_heads_category_fk')->references('id')->on('fee_categories')->restrictOnDelete();
            $table->index('fee_category_id', 'fee_heads_category_idx');
        });

        Schema::table('fee_heads', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('fee_heads', function (Blueprint $table) {
            $table->string('category', 30)->default('OTHER')->after('code');
        });

        foreach (DB::table('fee_heads')->select('id', 'fee_category_id')->get() as $head) {
            $code = DB::table('fee_categories')->where('id', $head->fee_category_id)->value('code') ?: 'OTHER';
            DB::table('fee_heads')->where('id', $head->id)->update(['category' => $code]);
        }

        Schema::table('fee_heads', function (Blueprint $table) {
            $table->dropForeign('fee_heads_category_fk');
            $table->dropIndex('fee_heads_category_idx');
            $table->dropColumn('fee_category_id');
        });

        Schema::dropIfExists('fee_categories');
    }
};
