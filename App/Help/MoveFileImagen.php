<?php

namespace App\Help;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

enum ImageFormat: string
{
    case JPG = 'jpg';
    case PNG = 'png';
    case WEBP = 'webp';
}

class MoveFileImagen
{
    protected $manager;

    public function __construct()
    {
        // Configurar el driver globalmente (GD en este caso)
        $this->manager = new ImageManager(new Driver());
    }

    public static function setImage(array $fileImage): ImageBuilder
    {
        return new ImageBuilder($fileImage);
    }

    public static function processImage(
        array $fileImage,
        int $width,
        ?int $height = null,
        string $photoDelete = '',
        ImageFormat $format = ImageFormat::WEBP,
        int $quality = 90,
        bool $maintainAspectRatio = false
    ): ?string {
        // Validar si el archivo es válido y es una imagen
        if (!$fileImage || $fileImage['error'] !== UPLOAD_ERR_OK || !self::isImage($fileImage)) {
            return null;
        }

        // Generar nombre único con extensión basada en el formato
        $extension = $format->value; // Asegurarse de que el formato esté en minúsculas
        $nameImg = bin2hex(random_bytes(16)) . '.' . $extension;

        // Crear directorio si no existe
        if (!is_dir(DIR_IMG)) {
            mkdir(DIR_IMG);
        }

        // Crear instancia para acceder al manager
        $instance = new self();

        // Procesar imagen
        $image = $instance->manager->read($fileImage["tmp_name"]);

        if ($height === null) {
            $image->scale(width: $width);
        } else {
            if ($maintainAspectRatio) {
                $image->cover($width, $height);
            } else {
                $image->resize($width, $height);
            }
        }

        // Guardar imagen según el formato especificado
        try {
            switch ($format) {
                case ImageFormat::JPG:
                    $image->toJpeg($quality)->save(DIR_IMG . $nameImg);
                    break;
                case ImageFormat::PNG:
                    $image->toPng()->save(DIR_IMG . $nameImg);
                    break;
                case ImageFormat::WEBP:
                default:
                    $image->toWebp($quality)->save(DIR_IMG . $nameImg);
                    break;
            }
        } catch (\Exception $e) {
            // Manejar errores de guardado
            return null;
        }

        // Eliminar imagen anterior si existe
        if ($photoDelete) {
            self::deleteImage(DIR_IMG . $photoDelete);
        }

        return $nameImg;
    }

    private static function isImage($file): bool
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        return in_array($file['type'], $allowedMimes);
    }

    private static function deleteImage(string $path): void
    {
        if (file_exists($path) && is_file($path)) {
            @unlink($path); // Suprimir errores, considerar logger en producción
        }
    }
}
