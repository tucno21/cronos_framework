<?php

use Cronos\Database\Migration;
use Cronos\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etiquetables', function ($table) {
            $table->id();
            $table->foreignId('etiqueta_id');
            $table->string('etiquetable_tipo', 255);
            $table->integer('etiquetable_id');
            $table->timestamp('created_at')->nullable();
            $table->unique(['etiqueta_id', 'etiquetable_tipo', 'etiquetable_id']);

            $table->foreign('etiqueta_id')->references('id')->on('etiquetas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etiquetables');
    }
};
