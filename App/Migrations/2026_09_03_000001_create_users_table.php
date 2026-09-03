<?php

use Cronos\Database\Migration;
use Cronos\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 191)->unique();
            $table->string('password', 255);
            $table->enum('role', ['admin', 'editor', 'user'])->default('user');
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
