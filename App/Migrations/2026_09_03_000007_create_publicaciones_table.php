<?php

use Cronos\Database\Migration;
use Cronos\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publicaciones', function ($table) {
            $table->id();
            $table->foreignId('usuario_id');
            $table->foreignId('categoria_id')->nullable();
            $table->string('titulo', 255);
            $table->string('slug', 191)->unique();
            $table->text('resumen')->nullable();
            $table->longText('contenido');
            $table->string('imagen_portada')->nullable();
            $table->enum('estado', ['borrador', 'publicado', 'archivado'])->default('borrador');
            $table->integer('vistas')->default(0);
            $table->timestamp('publicado_en')->nullable();
            $table->timestamps();
            $table->softDeletes('eliminado_en');
            $table->index('estado');

            $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->foreign('categoria_id')->references('id')->on('categorias')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publicaciones');
    }
};
