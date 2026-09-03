<?php

use Cronos\Database\Migration;
use Cronos\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rol_usuario', function ($table) {
            $table->foreignId('usuario_id');
            $table->foreignId('rol_id');
            $table->primary(['usuario_id', 'rol_id']);

            //plural en espanol: rol -> roles (constrained() derivaria 'rols')
            $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->foreign('rol_id')->references('id')->on('roles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rol_usuario');
    }
};
