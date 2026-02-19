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
        Schema::table('users', function (Blueprint $table) {
            // add columns only if they don't already exist
            if (! Schema::hasColumn('users', 'student_id')) {
                // student id in format ##-####-###
                $table->string('student_id')->unique()->nullable();
            }

            if (! Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('student');
            }

            if (! Schema::hasColumn('users', 'is_approved')) {
                $table->boolean('is_approved')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('users', 'student_id')) {
                $drop[] = 'student_id';
            }
            if (Schema::hasColumn('users', 'status')) {
                $drop[] = 'status';
            }
            if (Schema::hasColumn('users', 'role')) {
                $drop[] = 'role';
            }
            if (Schema::hasColumn('users', 'is_approved')) {
                $drop[] = 'is_approved';
            }

            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
