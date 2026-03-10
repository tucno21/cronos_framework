<?php

use Cronos\Database\Schema\Schema;
use Cronos\Database\Schema\Blueprint;

return new class {
    public string $table = 'blogs';

    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->string('slug', 150)->unique();
            $table->text('content');
            $table->integer('user_id')->unsigned();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
};
