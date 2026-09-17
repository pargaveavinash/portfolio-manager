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
        Schema::create('mutual_funds', function (Blueprint $table) {
            $table->id();
            $table->string('amfi_code')->unique();
            $table->string('isin')->nullable()->index();
            $table->string('amc_name');
            $table->string('scheme_name');
            $table->string('plan_type'); // DIRECT, REGULAR
            $table->string('option_type'); // GROWTH, IDCW
            $table->string('category')->nullable();
            $table->timestamps();

            $table->index('amc_name');
            $table->index('scheme_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutual_funds');
    }
};
