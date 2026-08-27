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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('portfolio_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('holding_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type', 20);

            $table->decimal('quantity', 20, 8);

            $table->decimal('price', 20, 6);

            $table->string('currency', 3)->default('INR');

            $table->timestamp('transaction_date');

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'portfolio_id',
                'holding_id',
                'transaction_date',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
