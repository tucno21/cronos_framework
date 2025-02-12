<?php

namespace Cronos\Http;

use Cronos\Http\HttpMethod;

class Request
{
    //la uri que se esta solicitando de la web
    protected string $uri;

    //el metodo que se esta solicitando de la web
    protected HttpMethod $method;

    //las cabeceras que se envian en la peticion
    protected array $headers = [];

    //los cookies que se envian en la peticion
    protected array $cookies = [];

    //los datos que se envian por get, post, put, patch, delete
    protected array $data = [];

    protected array $files = [];

    public function __construct()
    {
        $this->setupCorsHeaders();
        $this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->method = HttpMethod::from($_SERVER['REQUEST_METHOD']);
        $this->headers = getallheaders();
        $this->cookies = $_COOKIE;
        $this->data = $this->setData();
    }

    private function setupCorsHeaders(): void
    {
        $corsConfigs = [
            'allowed_origins' => 'Access-Control-Allow-Origin',
            'allowed_methods' => 'Access-Control-Allow-Methods',
            'allowed_headers' => 'Access-Control-Allow-Headers'
        ];

        foreach ($corsConfigs as $key => $header) {
            $allowed = configGet("cors.$key");
            if (!empty($allowed)) {
                header("$header: " . implode(', ', $allowed));
            }
        }
    }

    private function normalizeArrayKey(string $key): string
    {
        // Convierte keys como "field[]" o "field[0]" a "field"
        return preg_replace('/\[.*\]$/', '', $key);
    }

    private function processInputData(array $rawData): array
    {
        $processedData = [];

        foreach ($rawData as $key => $value) {
            $normalizedKey = $this->normalizeArrayKey($key);

            // Si la key termina en [] o [n], tratarlo como array
            if (preg_match('/\[(.*)\]$/', $key)) {
                if (!isset($processedData[$normalizedKey])) {
                    $processedData[$normalizedKey] = [];
                }

                // Si es un array simple (ej: tags[])
                if (empty(trim(preg_replace('/[\[\]]/', '', $key)))) {
                    if (is_array($value)) {
                        $processedData[$normalizedKey] = array_merge($processedData[$normalizedKey], $value);
                    } else {
                        $processedData[$normalizedKey][] = $value;
                    }
                }
                // Si tiene un índice específico (ej: tags[0])
                else {
                    $matches = [];
                    preg_match('/\[(\d+)\]/', $key, $matches);
                    if (isset($matches[1])) {
                        $processedData[$normalizedKey][$matches[1]] = $value;
                    } else {
                        $processedData[$normalizedKey][] = $value;
                    }
                }
            } else {
                $processedData[$normalizedKey] = $value;
            }
        }

        // Reindexar arrays numéricos para asegurar índices consecutivos
        foreach ($processedData as $key => $value) {
            if (is_array($value) && array_keys($value) !== range(0, count($value) - 1)) {
                $processedData[$key] = array_values($value);
            }
        }

        return $processedData;
    }

    private function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $file) {
            $normalizedKey = $this->normalizeArrayKey($key);

            // Si es un archivo único
            if (!is_array($file['name'])) {
                if (!isset($normalized[$normalizedKey])) {
                    $normalized[$normalizedKey] = [];
                }

                // Si es un archivo con key tipo "field[n]"
                if (preg_match('/\[(\d+)\]$/', $key, $matches)) {
                    $normalized[$normalizedKey][$matches[1]] = $file;
                } else {
                    $normalized[$normalizedKey] = $file;
                }
            }
            // Si es un array de archivos
            else {
                $fileCount = count($file['name']);
                $normalized[$normalizedKey] = [];

                for ($i = 0; $i < $fileCount; $i++) {
                    $normalized[$normalizedKey][$i] = [
                        'name' => $file['name'][$i],
                        'full_path' => $file['full_path'][$i],
                        'type' => $file['type'][$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'error' => $file['error'][$i],
                        'size' => $file['size'][$i]
                    ];
                }
            }
        }

        return $normalized;
    }

    private function parsePutInput(): array
    {
        $result = ['data' => [], 'files' => []];
        $input = file_get_contents("php://input");

        if (empty($input)) {
            return $result;
        }

        $boundary = substr($input, 0, strpos($input, "\r\n"));
        $parts = array_slice(explode($boundary, $input), 1, -1);

        // Array temporal para almacenar valores múltiples
        $tempArrays = [];

        foreach ($parts as $part) {
            if (empty($part)) continue;

            // Extraer el nombre del campo
            preg_match('/name="([^"]+)"/', $part, $nameMatch);
            if (empty($nameMatch)) continue;

            $fieldName = $nameMatch[1];
            $baseFieldName = $this->normalizeArrayKey($fieldName);

            // Verificar si es un archivo
            $isFile = preg_match('/Content-Type: (.*?)\r\n/', $part, $contentTypeMatch);

            if ($isFile) {
                // Procesar archivo como antes...
                $fileName = '';
                if (preg_match('/filename="([^"]*)"/', $part, $fileMatch)) {
                    $fileName = $fileMatch[1];
                }

                if (!empty($fileName)) {
                    $fileContent = substr($part, strpos($part, "\r\n\r\n") + 4, -2);
                    $tmpName = tempnam(sys_get_temp_dir(), 'php');
                    file_put_contents($tmpName, $fileContent);

                    $result['files'][$fieldName] = [
                        'name' => $fileName,
                        'full_path' => $fileName,
                        'type' => trim($contentTypeMatch[1]),
                        'tmp_name' => $tmpName,
                        'error' => 0,
                        'size' => filesize($tmpName)
                    ];
                }
            } else {
                $value = substr($part, strpos($part, "\r\n\r\n") + 4, -2);

                // Si el campo es un array (termina en [] o tiene índice)
                if (preg_match('/\[(.*)\]/', $fieldName)) {
                    if (!isset($tempArrays[$baseFieldName])) {
                        $tempArrays[$baseFieldName] = [];
                    }
                    $tempArrays[$baseFieldName][] = $value;
                } else {
                    $result['data'][$fieldName] = $value;
                }
            }
        }

        // Agregar los arrays procesados al resultado
        foreach ($tempArrays as $key => $values) {
            $result['data'][$key] = $values;
        }

        return $result;
    }

    //metodo para almacenar los datos que se envian por get, post, put, patch, delete y crear las propiedades dinamicas
    private function setData(): array
    {
        $data = [];
        $headers = $this->headers;
        $isJSON = isset($headers['Content-Type']) && $headers['Content-Type'] === 'application/json';

        if ($isJSON) {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            if ($this->method->value === 'PUT') {
                $putData = $this->parsePutInput();
                $data = $putData['data'];
                if (!empty($putData['files'])) {
                    $this->files = $this->normalizeFiles($putData['files']);
                }
            } else {
                if (!empty($_FILES)) {
                    $this->files = $this->normalizeFiles($_FILES);
                }
                $data = $this->method->value === 'GET' ? $_GET : $_POST;
            }
        }

        return $this->processInputData($data);
    }

    // Obtener la propiedad dinámica
    public function __get(string $name)
    {
        // Si la propiedad existe en los datos recibidos, devolver su valor
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }
    }

    // Establecer la propiedad dinámica
    public function __set(string $name, $value)
    {
        // Agregar la propiedad y su valor a los datos recibidos
        $this->data[$name] = $value;
        // $this->{$name} = $value;
    }

    public function uri(): string
    {
        //retorna la uri que se esta solicitando
        return $this->uri;
    }

    public function patch(): string
    {
        //retorna la uri que se esta solicitando
        return $this->uri;
    }

    public function method(): HttpMethod
    {
        //retorna el metodo que se esta solicitando
        return $this->method;
    }

    public function headers(string $key = null): object|string|null
    {
        //retorna los headers que se estan enviando
        if (is_null($key)) {
            $headers = $this->headers;
            return (object)$headers;
        }
        //convirtiendo la cabezeras en minuscula
        $lowercaseHeaders = array_change_key_case($this->headers, CASE_LOWER);
        // Convierte la clave a minúsculas
        $lowercaseKey = strtolower($key);
        // Busca la cabecera en minúsculas
        $value = $lowercaseHeaders[$lowercaseKey] ?? null;

        return $value;
    }

    public function cookies(string $key = null): object|string|null
    {
        //retorna los cookies que se estan enviando
        if (is_null($key)) {
            $cookies = $this->cookies;
            return (object)$cookies;
        }

        return $this->cookies[$key] ?? null;
    }

    //metodo para enviar todos los datos de la solicitud
    public function all(): object
    {
        return (object) $this->data;
    }

    //metodo para enviar el valor de un campo
    public function input(string $key): string
    {
        return $this->data[$key];
    }

    //metodo para enviar el valor booleano de un campo
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    //metodo para eliminar un campo y enviar los datos restantes
    public function except(string|array $keys): object
    {
        $data = $this->data;

        if (is_string($keys)) {
            $keys = [$keys];
        }

        foreach ($keys as $key) {
            unset($data[$key]);
        }

        return (object) $data;
    }

    //metodo para enviar solo los campos que se le indiquen
    public function only(string|array $keys): object
    {
        $data = $this->data;

        if (is_string($keys)) {
            $keys = [$keys];
        }

        $data = array_intersect_key($data, array_flip($keys));

        return (object) $data;
    }

    //metodo para enviar los archivos
    public function file(string $name): array
    {
        return $this->files[$name] ?? [];
    }

    //metodo para comprobar si existe un archivo
    public function hasFile(string $name): bool
    {
        return isset($this->files[$name]);
    }

    //metodo para guardar un archivo
    public function store(string $file, string $nameFile = null, string $nameFolder = null)
    {
        $file = $this->file($file);

        if (is_null($file)) {
            return null;
        }

        $nameFolder = is_null($nameFolder) ? env('PATH_FILE_STORAGE', 'storage') : $nameFolder;

        $path = DIR_PUBLIC . '/' . $nameFolder;

        //crear carpeta si no existe
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $nameImagen = is_null($nameFile) ? md5(uniqid(rand(), true)) : $nameFile;
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);

        $path = $nameFolder . '/' . $nameImagen . '.' . $extension;

        $tmp = $file['tmp_name'];

        move_uploaded_file($tmp, $path);

        return $nameImagen . '.' . $extension;
    }
}
