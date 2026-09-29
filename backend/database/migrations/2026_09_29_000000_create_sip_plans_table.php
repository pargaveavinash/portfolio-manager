<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sip_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('strategy')->default('optimization');
            $table->string('frequency'); // daily, weekly, monthly, quarterly
            $table->string('status')->default('active'); // active, paused, completed
            $table->date('start_date');
            $table->date('next_scheduled_date')->index();
            $table->date('end_date')->nullable();
            $table->string('timezone')->default('UTC');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sip_plans');
    }
};
