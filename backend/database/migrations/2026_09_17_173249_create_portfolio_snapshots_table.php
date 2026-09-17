<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('portfolio_snapshots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('portfolio_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('valuation_date');

            $table->decimal('invested_capital', 15, 6);
            $table->decimal('market_value', 15, 6);
            $table->decimal('cash_balance', 15, 6);
            $table->decimal('total_value', 15, 6);

            $table->timestamps();

            // Uniqueness for idempotency
            $table->unique(['portfolio_id', 'valuation_date']);

            // Index for date-range queries
            $table->index('valuation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_snapshots');
    }
};
