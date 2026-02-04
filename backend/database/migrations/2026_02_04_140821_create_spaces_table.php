<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('spaces', function (Blueprint $table) {
        $table->id();
        $table->string('name');       // e.g., "Library"
        $table->integer('occupancy'); // e.g., 15
        $table->integer('capacity');  // e.g., 100
        $table->string('status');     // e.g., "low", "medium", "high"
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
