<?php

use Cronos\Database\Migration;
use Cronos\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comentables', function ($table) {
            $table->id();
            $table->string('comentable_tipo', 255);
            $table->integer('comentable_id');
            $table->foreignId('usuario_id');
            $table->text('texto');
            $table->timestamp('created_at')->nullable();
            $table->index(['comentable_tipo', 'comentable_id']);

            $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comentables');
    }
};
