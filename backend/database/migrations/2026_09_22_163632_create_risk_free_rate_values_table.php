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
        Schema::create('risk_free_rate_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_free_rate_id')->constrained('risk_free_rates')->onDelete('cascade');
            $table->date('valuation_date');
            $table->decimal('rate', 10, 6);
            $table->timestamps();
            
            $table->unique(['risk_free_rate_id', 'valuation_date']);
        });

        // Add check constraint for non-negative rates, but only for Postgres as SQLite doesn't support ALTER TABLE ADD CONSTRAINT
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE risk_free_rate_values ADD CONSTRAINT chk_risk_free_rate_non_negative CHECK (rate >= 0);');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_free_rate_values');
    }
};
