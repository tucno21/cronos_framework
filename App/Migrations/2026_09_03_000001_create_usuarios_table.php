<?php

use Cronos\Database\Migration;
use Cronos\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function ($table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('correo', 191)->unique();
            $table->string('contrasena', 255);
            $table->enum('rol', ['admin', 'editor', 'usuario'])->default('usuario');
            $table->string('avatar')->nullable();
            $table->timestamp('correo_verificado_en')->nullable();
            $table->string('token_recordar', 100)->nullable();
            $table->foreignId('invitado_por')->nullable();
            $table->foreign('invitado_por')->references('id')->on('usuarios')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
