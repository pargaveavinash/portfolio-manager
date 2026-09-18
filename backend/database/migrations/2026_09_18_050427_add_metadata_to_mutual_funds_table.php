<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mutual_funds', function (Blueprint $table) {
            $table->foreignId('benchmark_id')->nullable()->constrained('benchmarks')->nullOnDelete();
            $table->string('sub_category')->nullable();
            $table->date('inception_date')->nullable();
            $table->decimal('ter', 5, 4)->nullable();
            $table->decimal('aum', 15, 2)->nullable();
            $table->text('exit_load')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mutual_funds', function (Blueprint $table) {
            $table->dropForeign(['benchmark_id']);
            $table->dropColumn([
                'benchmark_id',
                'sub_category',
                'inception_date',
                'ter',
                'aum',
                'exit_load',
            ]);
        });
    }
};
