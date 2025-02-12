<?php

namespace App\Help;

class MoveFile
{
    private $files;
    private $directory;
    private $useOriginalName = false;
    private $useDateTimeName = false;

    public function __construct(array $files = [], string $directory = 'archivos')
    {
        $this->files = $files;
        $this->directory = $directory;
    }

    public static function storeMultiple(array $files, string $directory = 'archivos'): self
    {
        return new self($files, $directory);
    }

    public static function storeSingle(array $file, string $directory = 'archivos'): self
    {
        // Convertir el archivo único en un array para reutilizar la lógica existente
        return new self($file, $directory);
    }

    public function randomName(): self
    {
        $this->useOriginalName = false;
        $this->useDateTimeName = false;
        return $this;
    }

    public function originalName(): self
    {
        $this->useOriginalName = true;
        $this->useDateTimeName = false;
        return $this;
    }

    public function dateName(): self
    {
        $this->useOriginalName = false;
        $this->useDateTimeName = true;
        return $this;
    }

    public function save()
    {
        if ($this->esArrayBidimensional($this->files)) {
            return $this->saveMultipleFiles();
        }

        return $this->saveSingleFile($this->files);
    }

    private function esArrayBidimensional(array $array): bool
    {
        return count($array) !== count($array, COUNT_RECURSIVE);
    }

    private function saveSingleFile(array $file): ?string
    {
        $uploadDir = DIR_IMG . $this->directory . '/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Verificación de seguridad básica
        if (!isset($file['tmp_name']) || !isset($file['name']) || $file['error'] !== 0) {
            return null;
        }

        // Verificar que el archivo temporal existe y es legible
        if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
            return null;
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

        // Determinar el nombre del archivo según los argumentos condicionales
        if ($this->useDateTimeName) {
            $baseName = date('d-m-y-His');
            $suffix = ''; // Inicializamos el sufijo vacío
            $counter = 1; // Contador para evitar colisiones

            // Verificamos si el archivo ya existe y generamos un nombre único
            do {
                $fileName = $baseName . $suffix . '.' . $extension;
                $suffix = '-' . $counter; // Agregamos un sufijo único
                $counter++;
            } while (file_exists($uploadDir . $fileName));
        } elseif ($this->useOriginalName) {
            $fileName = str_replace(' ', '-', $file['name']);
        } else {
            $fileName = md5(uniqid(rand(), true)) . '.' . $extension;
        }

        // Usar copy en lugar de move_uploaded_file para soportar tanto POST como PUT
        if (copy($file['tmp_name'], $uploadDir . $fileName)) {
            // Eliminar el archivo temporal después de copiarlo
            @unlink($file['tmp_name']);
            return $fileName;
        }

        return null;
    }

    private function saveMultipleFiles(): array
    {
        $uploadedFiles = [];
        $uploadDir = DIR_IMG . $this->directory . '/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($this->files as $index => $file) {
            // Verificación de seguridad básica
            if (!isset($file['tmp_name']) || !isset($file['name']) || $file['error'] !== 0) {
                continue;
            }

            // Verificar que el archivo temporal existe y es legible
            if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
                continue;
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

            // Determinar el nombre del archivo según los argumentos condicionales
            if ($this->useDateTimeName) {
                $baseName = date('d-m-y-His');
                $suffix = ''; // Inicializamos el sufijo vacío
                $counter = 1; // Contador para evitar colisiones

                // Verificamos si el archivo ya existe y generamos un nombre único
                do {
                    $fileName = $baseName . $suffix . '.' . $extension;
                    $suffix = '-' . $counter; // Agregamos un sufijo único
                    $counter++;
                } while (file_exists($uploadDir . $fileName));
            } elseif ($this->useOriginalName) {
                $fileName = str_replace(' ', '-', $file['name']);
            } else {
                $fileName = md5(uniqid(rand(), true)) . '.' . $extension;
            }

            // Usar copy en lugar de move_uploaded_file para soportar tanto POST como PUT
            if (copy($file['tmp_name'], $uploadDir . $fileName)) {
                // Eliminar el archivo temporal después de copiarlo
                @unlink($file['tmp_name']);
                $uploadedFiles[$index] = $fileName;
            }
        }

        return $uploadedFiles;
    }
}
