<?php

use Cronos\Database\Migration;
use Cronos\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function ($table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('slug', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->foreignId('categoria_padre_id')->nullable();
            $table->timestamps();

            //FK auto-referencial: arbol de categorias
            $table->foreign('categoria_padre_id')->references('id')->on('categorias')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
