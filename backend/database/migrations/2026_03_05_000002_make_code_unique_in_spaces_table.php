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
        Schema::table('spaces', function (Blueprint $table) {
            // if the code column doesn't exist yet, create it first
            if (!Schema::hasColumn('spaces', 'code')) {
                $table->string('code', 4)->nullable();
            }
            $table->string('code', 4)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spaces', function (Blueprint $table) {
            $table->string('code', 4)->nullable()->change();
        });
    }
};
