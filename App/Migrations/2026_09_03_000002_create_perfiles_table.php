<?php

use Cronos\Database\Migration;
use Cronos\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles', function ($table) {
            $table->id();
            $table->foreignId('usuario_id');
            $table->text('biografia')->nullable();
            $table->string('telefono', 50)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('sitio_web')->nullable();
            $table->timestamps();

            //usuario_id UNIQUE garantiza la relacion 1:1
            $table->unique('usuario_id');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles');
    }
};
