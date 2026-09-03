<?php

use Cronos\Database\Migration;
use Cronos\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function ($table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
