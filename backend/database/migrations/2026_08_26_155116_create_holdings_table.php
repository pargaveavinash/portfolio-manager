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
        Schema::create('holdings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('portfolio_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('symbol', 30);
            $table->string('name');
            $table->string('asset_type', 30);

            $table->decimal('quantity', 20, 8);
            $table->decimal('average_price', 20, 6);

            $table->string('currency', 3)->default('INR');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['portfolio_id', 'symbol']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holdings');
    }
};
