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
        Schema::create('benchmark_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('benchmark_id')->constrained('benchmarks')->cascadeOnDelete();
            $table->decimal('value', 20, 6);
            $table->date('valuation_date');
            $table->timestamps();

            $table->unique(['benchmark_id', 'valuation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benchmark_values');
    }
};
