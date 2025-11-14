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
        Schema::create('weekly_caches', function (Blueprint $table) {
            $table->id();
            $table->timestamp('range_start_at');
            $table->timestamp('range_end_at');
            $table->string('name', 20);
            $table->string('unit', 20);
            $table->decimal('value_avg', 20, 5);
            $table->decimal('value_min', 20, 5);
            $table->decimal('value_max', 20, 5);
            $table->string('source', 20)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['name', 'range_start_at']);
            $table->index('updated_at');
            $table->index(['range_start_at', 'range_end_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_caches');
    }
};
