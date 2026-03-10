<?php

use Cronos\Database\Schema\Schema;
use Cronos\Database\Schema\Blueprint;

return new class {
    public string $table = 'users';

    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('email', 60)->unique();
            $table->string('password', 60);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
};
