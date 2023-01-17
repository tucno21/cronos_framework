<?php

namespace Cronos\Storage;

class Image

{
    private $image;

    private string $mime;

    private string $extension;

    private $originalWidth;

    private $originalHeight;


    public static function make($contentImage): self
    {
        return new self($contentImage);
    }

    protected function __construct($image_path)
    {
        $this->extension = pathinfo($image_path['name'], PATHINFO_EXTENSION);

        //file_get_contents sirve para leer un archivo y devolver el contenido como una cadena
        $fileContent = file_get_contents($image_path['tmp_name']);
        //imagecreatefromstring sirve para crear una imagen a partir de una cadena de datos

        $info = getimagesizefromstring($fileContent);
        $this->mime = $info['mime']; // image/jpeg
        list($this->originalWidth, $this->originalHeight) = $info;


        // Crear un recurso de imagen en memoria para la imagen original
        switch ($this->mime) {
            case 'image/jpeg':
                $this->image = imagecreatefromstring($fileContent);
                break;
            case 'image/png':
                $this->image = imagecreatefromstring($fileContent);
                break;
            case 'image/gif':
                $this->image = imagecreatefromstring($fileContent);
                break;
            default:
                throw new \Exception('El tipo de imagen no es válido');
        }
    }

    public function resize(int $width = null, int $height = null): self
    {
        $original_width = imagesx($this->image);
        $original_height = imagesy($this->image);

        if ($width && $height) {
            $new_image = imagecreatetruecolor($width, $height);
            imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $original_width, $original_height);
            $this->image = $new_image;
        } elseif ($width) {
            $ratio = $width / $original_width;
            $new_height = $original_height * $ratio;
            $new_image = imagecreatetruecolor($width, intval($new_height));
            imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, intval($new_height), $original_width, $original_height);
            $this->image = $new_image;
        } elseif ($height) {
            $ratio = $height / $original_height;
            $new_width = $original_width * $ratio;
            $new_image = imagecreatetruecolor(intval($new_width), $height);
            imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, intval($new_width), $height, $original_width, $original_height);
            $this->image = $new_image;

            // Liberar los recursos de imagen en memoria
            imagedestroy($new_image);
        }

        return $this;
    }

    public function save(string $nameFile = null, string $nameFolder = null): string
    {
        $nameFolder = is_null($nameFolder) ? env('PATH_FILE_STORAGE', 'storage') : $nameFolder;

        $path = DIR_PUBLIC . '/' . $nameFolder;

        //crear carpeta si no existe
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $nameImagen = is_null($nameFile) ? md5(uniqid(rand(), true)) : $nameFile;

        $path = $path . '/' . $nameImagen . '.' . $this->extension;

        imagejpeg($this->image, $path, 100);
        // Liberar los recursos de imagen en memoria
        imagedestroy($this->image);

        return $nameImagen . '.' . $this->extension;
    }
}
