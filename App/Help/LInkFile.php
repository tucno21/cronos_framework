<?php

namespace App\Help;


class LInkFile
{
    /**
     * Genera una URL completa para un archivo almacenado.
     *
     * @param string $nameFile Nombre del archivo.
     * @param string|null $folderName Nombre de la carpeta (opcional).
     * @return string URL completa del archivo.
     * @throws \InvalidArgumentException Si el nombre del archivo está vacío.
     */
    public static function setName($nameFile, $folderName = null)
    {
        if (empty($nameFile)) {
            throw new \InvalidArgumentException("El nombre del archivo no puede estar vacío.");
        }

        // Sanitizar el nombre del archivo
        $nameFile = basename($nameFile);

        // Obtener la URL base y la ruta de almacenamiento
        $baseUrl = base_url;
        $pathStorage = env('PATH_FILE_STORAGE', 'public');

        // Construir la URL base
        $url = "{$baseUrl}/{$pathStorage}";

        // Agregar la carpeta si se proporciona
        if ($folderName !== null) {
            $folderName = basename($folderName); // Sanitizar el nombre de la carpeta
            $url .= "/{$folderName}";
        }

        // Agregar el nombre del archivo y retornar la URL completa
        $url .= "/{$nameFile}";

        //retornar la URL
        return $url;
    }
}
