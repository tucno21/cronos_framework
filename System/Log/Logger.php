<?php

namespace Cronos\Log;

use DateTime;

/**
 * Logger - Sistema de logging de Cronos Framework
 * 
 * Registra errores, advertencias y mensajes informativos en archivos de log.
 * Los logs se rotan diariamente automáticamente.
 */
class Logger
{
    /**
     * Directorio donde se almacenan los logs
     */
    private static string $logPath;

    /**
     * Niveles de log
     */
    public const LEVEL_ERROR = 'ERROR';
    public const LEVEL_WARNING = 'WARNING';
    public const LEVEL_INFO = 'INFO';

    /**
     * Inicializa el directorio de logs
     */
    private static function init(): void
    {
        if (!isset(self::$logPath)) {
            self::$logPath = dirname(__DIR__, 2) . '/storage/logs';

            // Crear directorio si no existe
            if (!is_dir(self::$logPath)) {
                mkdir(self::$logPath, 0755, true);
            }
        }
    }

    /**
     * Obtiene el nombre del archivo de log para la fecha actual
     */
    private static function getLogFile(): string
    {
        self::init();
        $date = new DateTime();
        return self::$logPath . '/cronos-' . $date->format('Y-m-d') . '.log';
    }

    /**
     * Formatea el mensaje de log
     */
    private static function formatMessage(string $level, string $message, array $context = []): string
    {
        $datetime = new DateTime();
        $datetimeStr = $datetime->format('Y-m-d H:i:s');

        $log = "[{$datetimeStr}] {$level}: {$message}";

        if (!empty($context)) {
            $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $log .= " | {$contextJson}";
        }

        return $log . PHP_EOL;
    }

    /**
     * Escribe un mensaje en el archivo de log
     */
    private static function write(string $message): void
    {
        $logFile = self::getLogFile();

        // Crear archivo si no existe
        if (!file_exists($logFile)) {
            touch($logFile);
            chmod($logFile, 0644);
        }

        // Escribir el mensaje (append)
        file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    }

    /**
     * Registra un error
     * 
     * @param string $message Mensaje del error
     * @param array $context Contexto adicional (datos relevantes)
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        $formatted = self::formatMessage(self::LEVEL_ERROR, $message, $context);
        self::write($formatted);
    }

    /**
     * Registra una advertencia
     * 
     * @param string $message Mensaje de advertencia
     * @param array $context Contexto adicional
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        $formatted = self::formatMessage(self::LEVEL_WARNING, $message, $context);
        self::write($formatted);
    }

    /**
     * Registra un mensaje informativo
     * 
     * @param string $message Mensaje informativo
     * @param array $context Contexto adicional
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        $formatted = self::formatMessage(self::LEVEL_INFO, $message, $context);
        self::write($formatted);
    }

    /**
     * Registra una excepción completa
     * 
     * @param \Throwable $exception La excepción a registrar
     * @param array $context Contexto adicional
     * @return void
     */
    public static function exception(\Throwable $exception, array $context = []): void
    {
        $message = get_class($exception) . ': ' . $exception->getMessage();

        $exceptionContext = array_merge($context, [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        self::error($message, $exceptionContext);
    }
}
