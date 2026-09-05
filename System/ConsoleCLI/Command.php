<?php

declare(strict_types=1);

namespace Cronos\ConsoleCLI;

/**
 * Clase base para comandos de consola personalizados en Cronos Framework.
 */
abstract class Command
{
    /**
     * El nombre o firma del comando (ej: 'mail:send').
     */
    protected string $signature = '';

    /**
     * Breve descripción del comando.
     */
    protected string $description = '';

    /**
     * Argumentos pasados al comando desde la CLI.
     */
    protected array $arguments = [];

    /**
     * Inicializa el comando con los argumentos recibidos.
     */
    public function __construct(array $arguments = [])
    {
        $this->arguments = $arguments;
    }

    /**
     * Ejecuta la lógica del comando.
     */
    abstract public function handle(): int;

    /**
     * Obtiene el valor de un argumento posicional.
     */
    protected function argument(int $index, mixed $default = null): mixed
    {
        return $this->arguments[$index] ?? $default;
    }

    /**
     * Escribe un mensaje con salto de línea.
     */
    protected function line(string $message): void
    {
        echo $message . PHP_EOL;
    }

    /**
     * Escribe un mensaje en verde (éxito).
     */
    protected function info(string $message): void
    {
        echo "\033[32m" . $message . "\033[0m" . PHP_EOL;
    }

    /**
     * Escribe un mensaje en amarillo (advertencia).
     */
    protected function warn(string $message): void
    {
        echo "\033[33m" . $message . "\033[0m" . PHP_EOL;
    }

    /**
     * Escribe un mensaje en rojo (error).
     */
    protected function error(string $message): void
    {
        echo "\033[31m" . $message . "\033[0m" . PHP_EOL;
    }

    /**
     * Obtiene la firma del comando.
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * Obtiene la descripción del comando.
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
