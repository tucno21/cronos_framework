<?php

use Cronos\Database\Migration;
use Cronos\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comentarios', function ($table) {
            $table->id();
            $table->foreignId('publicacion_id');
            $table->foreignId('usuario_id');
            $table->text('contenido');
            $table->timestamps();

            $table->foreign('publicacion_id')->references('id')->on('publicaciones')->cascadeOnDelete();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comentarios');
    }
};
