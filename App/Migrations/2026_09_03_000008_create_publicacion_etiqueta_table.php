<?php

use Cronos\Database\Migration;
use Cronos\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publicacion_etiqueta', function ($table) {
            $table->foreignId('publicacion_id');
            $table->foreignId('etiqueta_id');
            $table->primary(['publicacion_id', 'etiqueta_id']);

            //plural en espanol: publicacion -> publicaciones (constrained() derivaria mal)
            $table->foreign('publicacion_id')->references('id')->on('publicaciones')->cascadeOnDelete();
            $table->foreign('etiqueta_id')->references('id')->on('etiquetas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publicacion_etiqueta');
    }
};
