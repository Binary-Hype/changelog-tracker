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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('owner');
            $table->string('repo');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('check_interval_minutes')->default(30);
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('include_prereleases')->default(false);
            $table->timestamps();

            $table->unique(['owner', 'repo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
