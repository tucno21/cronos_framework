<?php

use Cronos\Database\Migration;
use Cronos\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_resets', function ($table) {
            $table->id();
            $table->string('email', 191)->index();
            $table->string('token', 255);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
