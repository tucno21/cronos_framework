<?php

use Cronos\Database\Migration;
use Cronos\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function ($table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
