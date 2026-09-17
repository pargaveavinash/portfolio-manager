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
        Schema::create('mutual_fund_navs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mutual_fund_id')->constrained('mutual_funds')->cascadeOnDelete();
            $table->decimal('nav', 20, 6);
            $table->date('nav_date');
            $table->timestamps();

            $table->unique(['mutual_fund_id', 'nav_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutual_fund_navs');
    }
};
