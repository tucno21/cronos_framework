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
        //inicializar los headers Origins que se aceptan
        $allowed_origins = configGet('cors.allowed_origins');
        if ($allowed_origins !== null && $allowed_origins !== []) {
            //['*']
            $origins = implode(', ', $allowed_origins);
            header("Access-Control-Allow-Origin: $origins");
        }

        //inicializar los headers Methods que se aceptan
        $allowed_methods = configGet('cors.allowed_methods');
        if ($allowed_methods !== null && $allowed_methods !== []) {
            //['GET', 'POST', 'PUT',  'DELETE']
            $metodos = implode(', ', $allowed_methods);
            header("Access-Control-Allow-Methods: $metodos");
        }

        //inicializar los headers Headers que se aceptan
        $allowed_headers = configGet('cors.allowed_headers');
        if ($allowed_headers !== null && $allowed_headers !== []) {
            //['*']
            $headers = implode(', ', $allowed_headers);
            header("Access-Control-Allow-Headers: $headers");
        }

        //capturar la uri que se esta solicitando y lo convierte en un array
        $this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        //capturar el metodo que se esta solicitando (get, post, put, patch, delete)
        $this->method = HttpMethod::from($_SERVER['REQUEST_METHOD']);
        //las cabeceras que se envian en la peticion
        $this->headers = getallheaders();
        //los cookies que se envian en la peticion
        $this->cookies = $_COOKIE;
        //capturar los datos que se envian por get, post, put, patch, delete
        $this->data = $this->setData();
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

        return $this->headers[$key] ?? null;
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

    //metodo para almacenar los datos que se envian por get, post, put, patch, delete y crear las propiedades dinamicas
    private function setData()
    {
        //capturar los datos que se envian por get, post, put, patch, delete
        $data = [];

        // Obtener los headers de la solicitud
        $headers = getallheaders();

        // Verificar si la solicitud es JSON
        $isJSON = isset($headers['Content-Type']) && $headers['Content-Type'] === 'application/json';

        // Obtener los datos de la solicitud
        if ($isJSON) {
            // Si la solicitud es JSON, decodificar el cuerpo de la solicitud
            $data = json_decode(file_get_contents('php://input'), true);
        } else {
            // Si la solicitud no es JSON, obtener los datos de la solicitud

            //si el metodo es PUT
            if ($this->method->value === 'PUT') {
                //obtener los datos de la solicitud PUT
                $data = $this->setPUT();
            } else if ($this->method->value === 'GET') {
                //obtener los datos de la solicitud GET
                $data = $_GET;
            } else {
                // Capturar archivos en solicitudes POST
                if (!empty($_FILES)) {
                    $this->files = $_FILES;
                }
                //agregar al ultimo los datos de la solicitud POST
                $data = $_POST;
            }
        }

        // Crear las propiedades dinámicas
        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }

        return $data;
    }

    //metodo para obtener los datos de la solicitud PUT
    private function setPUT()
    {
        $dataInPutsText = []; //ejemplo: ['title' => 'miweb', 'url' => 'www.google.com','description' => 'hola jorge']
        $files = []; //ejemplo: ['image' => ['name'=>'bb.png','full_path'=>'bb.jpg','type'=>'image/png','tmp_name'=>'C:\Users\Carlos\AppData\Local\Temp\php26F4.tmp','error'=>0,'size'=>181777]]
        // Leer la carga útil de la solicitud
        $inputs = file_get_contents("php://input");

        // Si la carga útil no está vacía, procesar los datos y los archivos
        if (!empty($inputs)) {
            // Separar la carga útil en múltiples partes, utilizando el delimitador de multipart
            $delimiter = substr($inputs, 0, strpos($inputs, "\r\n")); //string(40) "------WebKitFormBoundarynuxHwAERZNdXgb46"

            // Separar los datos y los archivos en partes separadas
            $inputsArray = array_slice(explode($delimiter, $inputs), 1); //cada input es un string dentro de un array
            //eliminar el ultimo elemento del array
            array_pop($inputsArray);

            // Recorrer cada parte de la carga útil
            foreach ($inputsArray as $input) {
                // Si la parte no está vacía, procesarla
                if ($input !== "--\r \n") {
                    // Obtener el nombre del campo
                    preg_match('/name="([^"]+)"/', $input, $match); //array(2) { [0]=> string(12) "name="title"" [1]=> string(5) "title" }
                    $fieldName = $match[1];

                    // Obtener el tipo de contenido
                    if (preg_match('/Content-Type: (.*)/', $input, $match) === 1) {
                        preg_match('/Content-Type: (.*)/', $input, $match); //array(2) { [0]=> string(24) "Content-Type: image/png" [1]=> string(9) "image/png" }
                        $contentType = $match[1];
                        //limpiar espacios en blanco
                        $contentType = trim($contentType);
                    } else {
                        $contentType = null;
                    }

                    // Obtener el valor del campo
                    // $value = substr($input, strpos($input, "\r\n\r\n") + 4, -2); //string(5) "hola " o el contenido de la imagen
                    $pos = strpos($input, "\r\n\r\n");
                    if ($pos !== false) {
                        $value = substr($input, $pos + 4, -2);
                    } else {
                        $value = "";
                    }

                    // Si el tipo de contenido es un archivo, procesarlo
                    if (isset($contentType)) {
                        // Obtener el nombre del archivo
                        // preg_match('/filename="([^"]+)"/', $input, $match); //array(2) { [0]=> string(19) "filename="bb.png"" [1]=> string(6) "bb.png" }
                        preg_match('/filename="([^"]*)"/', $input, $match); //array(2) { [0]=> string(19) "filename="bb.png"" [1]=> string(6) "bb.png" }
                        $filename = $match[1];

                        if ($filename == '' || $filename == null) {
                            $file = [
                                'name' => '',
                                'full_path' => '',
                                'type' => '',
                                'tmp_name' => '',
                                'error' => 4,
                                'size' => 0,
                            ];

                            // Agregar el archivo al array de archivos
                            $files[$fieldName] = $file;
                        } else {
                            // Obtener el contenido del archivo
                            $fileContent = substr($input, strpos($input, "\r\n\r\n") + 4, -2); //string(181777) "GIF89a..."

                            // Obtener la ruta temporal del archivo
                            $tmpName = tempnam(sys_get_temp_dir(), 'php'); //string(36) "C:\Users\Carlos\AppData\Local\Temp\php26F4.tmp"

                            // Escribir el contenido del archivo en la ruta temporal
                            file_put_contents($tmpName, $fileContent);

                            // Obtener el tamaño del archivo
                            $size = filesize($tmpName);

                            // Obtener el código de error del archivo
                            $error = ($size > 0) ? 0 : 1;

                            // Crear un array con los datos del archivo
                            $file = [
                                'name' => $filename,
                                'full_path' => $tmpName,
                                'type' => $contentType,
                                'tmp_name' => $tmpName,
                                'error' => $error,
                                'size' => $size,
                            ];

                            // Agregar el archivo al array de archivos
                            $files[$fieldName] = $file;
                        }
                    } else {
                        // Si el tipo de contenido no es un archivo, agregarlo al array de datos
                        $dataInPutsText[$fieldName] = $value;
                    }
                }
            }
        }

        if (!empty($files)) {
            $this->files = $files;
        }

        $data = $dataInPutsText;

        return $data;
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
