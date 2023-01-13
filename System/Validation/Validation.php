<?php

namespace Cronos\Validation;

use Cronos\Validation\MessageError;

class Validation
{
    /**
     * los datos a validar (inputs)
     */
    private static array $inputs = [];
    /**
     * reglas de validacion
     */
    private static array $rules = [];
    /**
     * mensajes de errores
     */
    private static $errors = [];
    /**
     * mensajes de validacion
     */
    private static $customMessages = [];

    public function validate(array|object $inputs, array $rules)
    {
        if (is_object($inputs))
            $inputs = (array)$inputs;

        self::$inputs = $inputs;
        self::$rules = $rules;

        //verificar que los Inputs no esten vacios
        if (!empty($inputs)) {
            $this->callRules();

            if (count(self::$errors) === 0) {
                //eliminar los errores de la sesion que se hayan guardado
                session()->deleteErrorsInputs();
                return true;
            } else {
                $error = self::$errors;
                return RESULT_TYPE === 'array' ? $error : (object) $error;
            }
        }

        return 'error';
    }

    /**
     * ejecuta las reglas de validacion
     */
    private function callRules()
    {
        // $pattern = '[\p{L}]+';
        // $regex = '/^(' . $pattern . ')$/u';
        // $regex = '/^([\p{L}]+)$/u'; // only letters
        $regex = '/^[a-z_]+$/i'; // only letters
        // $regex = '/[^A-Za-zÀ-ÿ]+/u'; //busca solo letras

        //recorrer los [nameInput => [rules, rules]]
        foreach (self::splitRules() as $nameInput => $rules) {

            //recorrer los [rules, rules] regla por regla
            foreach ($rules as $rule) {

                //trim — Elimina espacio del inicio y el final de la cadena
                $rule = trim($rule);
                //ucfirst — Convierte el primer caracter de una cadena a mayúsculas
                //crear el nombre del metodo de la regla
                $ruleMethod = 'validate' . ucfirst($rule);

                //preg_match — Realiza una comparación con una expresión regular
                //preg_match($regex, $rule) //busca solo letras sin :
                if (preg_match($regex, $rule)) {

                    //verificar si el metodo existe en la clase
                    if (method_exists(Validation::class, $ruleMethod)) {
                        //invocar el metodo(nombrde del input, regla)
                        self::$ruleMethod($nameInput, $rule);
                    } else {
                        //si no existe el metodo, agregar el error
                        self::$errors[] = 'El metodo ' . $ruleMethod . ' no existe';
                    }
                } else {
                    //'min:3'   //unique:model,colum
                    //retorna un array separando la regla por :
                    $ruleParam = explode(':', $rule);

                    //nombre de la regla
                    $rule = trim($ruleParam[0]);
                    //parametro de la regla en array
                    $params = explode(',', $ruleParam[1]);

                    //crear el nombre del metodo de la regla
                    $ruleMethod = 'validate' . ucfirst($rule);

                    //verificar si el metodo existe en la clase
                    if (method_exists(Validation::class, $ruleMethod)) {
                        //invocar el metodo(nombrde del input, regla, parametro)
                        self::$ruleMethod($nameInput, $rule, $params);
                    } else {
                        //si no existe el metodo, agregar el error
                        self::$errors[] = 'El metodo ' . $ruleMethod . ' no existe';
                    }
                }
            }
        }
    }

    /**
     * separa las reglas de validacion
     * $rules = [ 'title' => 'required|text|min:3|max:15', 'email' => 'required|email','password' => 'required|min:6']
     * separa la reglas de los inputs y retorna en un array
     * [nameInput => [rules, rules]]
     * $rules = ['title' => ['required', 'text', 'min:3', 'max:13'],'email' => ['required', 'email'],'password' => ['required', 'min:3']]
     */
    private static function splitRules()
    {
        //crear un array separando la reglas
        $rules = [];

        foreach (self::$rules as $ruleName => $rule) {
            $rules[$ruleName] = explode('|', $rule);
        }
        return $rules;
    }

    /**
     * Buscar el nombre del input(regla) en el array de inputs(externo)
     */
    private static function searchInput(string $nameInput)
    {
        if (array_key_exists($nameInput, self::$inputs)) {
            return self::$inputs[$nameInput];
        }
        return null;
    }

    /**
     * retorna los errores
     */
    private static function addError(string $nameInput, string $rule, array $attributes = [])
    {
        //verifica si no existe el nombreImput en el array de errores
        if (!array_key_exists($nameInput, self::$errors)) {
            //enviar datos a la clase de errors (nombre del input, regla, atributos)
            //retorna el input y la regla de aclaracion de error
            self::$errors[$nameInput] = (string)(new MessageError($nameInput, $rule, $attributes));
        }
    }


    /**
     * validaciones de los inputs
     * https://regex101.com/
     */
    /**
     * valida qee solo sea letra alfabetica sin espacio
     */
    private static function validateAlpha(string $nameInput, string $rule)
    {
        $value = self::searchInput($nameInput);
        //El campo bajo validación debe ser completamente caracteres alfabéticos

        if (!preg_match('/^[\pL\pM]+$/u', $value)) {
            self::addError($nameInput, $rule);
        }
    }

    /**
     * valida el campo puede tener caracteres alfanuméricos, así como guiones y guiones bajos
     */
    private static function validateAlpha_dash(string $nameInput, string $rule)
    {
        $value = self::searchInput($nameInput);
        //El campo bajo validación puede tener caracteres alfanuméricos, así como guiones y guiones bajos

        // if (!preg_match('/\A[A-ZÀ-ÿ0-9_-]+\z/i', $value)) {
        if (!preg_match('/^[\pL\pM\pN_-]+$/u', $value)) {
            self::addError($nameInput, $rule);
        }
    }

    /**
     * valida el campo puede tener caracteres alfabéticos y espacio
     */
    private static function validateAlpha_space(string $nameInput, string $rule)
    {
        $value = self::searchInput($nameInput);

        if (!preg_match('/\A[A-ZÀ-ÿ ]+\z/i', $value)) {
            self::addError($nameInput, $rule);
        }
    }

    /**
     * valida el campo puede tener caracteres alfabéticos y numericos y sin espacio
     */
    private static function validateAlpha_numeric(string $nameInput, string $rule)
    {
        $value = self::searchInput($nameInput);

        // if (!ctype_alnum($value)) {
        if (!preg_match('/^[\pL\pM\pN]+$/u', $value)) {
            self::addError($nameInput, $rule);
        }
    }

    /**
     * valida el campo puede tener caracteres alfabéticos y numericos y con espacio
     */
    private static function validateAlpha_numeric_space(string $nameInput, string $rule)
    {
        $value = self::searchInput($nameInput);

        if (!preg_match('/\A[A-Z0-9À-ÿ ]+\z/i', $value)) {
            self::addError($nameInput, $rule);
        }
    }

    private static function validateDecimal(string $nameInput, string $rule)
    {
        $value = self::searchInput($nameInput);

        if (!preg_match('/^(\d+(\.\d+)?)$/', $value)) {
            self::addError($nameInput, $rule);
        }
    }

    private static function validateInteger(string $nameInput, string $rule)
    {
        //acepta numeros
        $value = self::searchInput($nameInput);
        if (!preg_match('/\A[\-+]?\d+\z/', $value)) {
            self::addError($nameInput, $rule);
        }
    }

    private static function validateIs_natural(string $nameInput, string $rule)
    {
        $value = self::searchInput($nameInput);
        //validar que sea un numero entero positivo
        if (!preg_match('/\A[0-9]+\z/', $value)) {
            self::addError($nameInput, $rule);
        }
    }

    private static function validateIs_natural_no_zero(string $nameInput, string $rule)
    {
        $value = self::searchInput($nameInput);
        //validar que sea un numero entero positivo mayor a cero
        if (!preg_match('/\A[1-9][0-9]*\z/', $value)) {
            self::addError($nameInput, $rule);
        }
    }

    private static function validateNumeric(string $nameInput, string $rule)
    {
        $value = self::searchInput($nameInput);
        if (!preg_match('/\A[\-+]?\d*\.?\d+\z/', $value)) {
            self::addError($nameInput, $rule);
        }
    }

    private static function validateRequired(string $nameInput, string $rule)
    {
        $value = self::searchInput($nameInput);
        if (empty($value) || is_null($value)) {
            self::addError($nameInput, $rule);
        }
    }

    private static function validateEmail(string $nameInput, string $rule)
    {
        $value = self::searchInput($nameInput);
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            self::addError($nameInput, $rule);
        }
    }

    private static function validateUrl(string $nameInput, string $rule)
    {
        $value = self::searchInput($nameInput);

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            self::addError($nameInput, $rule);
        }
    }

    private static function validateMin(string $nameInput, string $rule, $params)
    {
        $value = self::searchInput($nameInput);

        $length = mb_strlen((string) $value);
        if (count($params) === 1) {
            $min = (int)min($params);
            if (!is_null($min) && $length < $min) {
                self::addError($nameInput, 'min', [$min]);
            }
        } else {
            throw new \Exception("La regla mínima toma solo un parámetro");
        }
    }

    private static function validateMax(string $nameInput, string $rule, $params)
    {
        $value = self::searchInput($nameInput);

        $length = mb_strlen((string) $value);
        if (count($params) === 1) {
            $max = (int)max($params);
            if (!is_null($max) && $length > $max) {
                self::addError($nameInput, 'max', [$max]);
            }
        } else {
            throw new \Exception("La regla mínima toma solo un parámetro");
        }
    }

    private static function validateString(string $nameInput, string $rule)
    {
        $value = self::searchInput($nameInput);

        if (!is_string($value)) {
            self::addError($nameInput, $rule);
        }
    }


    private static function validateText(string $nameInput, string $rule)
    {
        $value = self::searchInput($nameInput);

        if (!preg_match('/(\w+)([\W+^\s])/i', (string) $value)) {
            self::addError($nameInput, $rule);
        }
    }

    ////////////////////////////////////////////////////////////////////////

    private static function validateBetween(string $nameInput, string $rule, $params)
    {
        $value = self::searchInput($nameInput);
        $length = mb_strlen($value);
        if (count($params) === 2) {
            $min = (int)min($params);
            $max = (int)max($params);
            if (!is_null($min) && !is_null($max) && ($length < $min || $length > $max)) {
                self::addError($nameInput, 'between', [$min, $max]);
            }
        } else {
            throw new \Exception("The between rule must take two parameters");
        }
    }

    private static function validateDatetime(string $nameInput, string $rule)
    {
        $format = 'Y-m-d H:i:s';

        $value = self::searchInput($nameInput);
        $date = \DateTime::createFromFormat($format, $value);
        $errors = \DateTime::getLastErrors();
        if ($errors['error_count'] > 0 || $errors['warning_count'] > 0 || $date === false) {
            self::addError($nameInput, 'datetime', [$format]);
        }
    }

    private static function validateTime(string $nameInput, string $rule)
    {
        $format = 'H:i:s';

        $value = self::searchInput($nameInput);
        $date = \DateTime::createFromFormat($format, $value);
        $errors = \DateTime::getLastErrors();
        if ($errors['error_count'] > 0 || $errors['warning_count'] > 0 || $date === false) {
            self::addError($nameInput, $rule, [$format]);
        }
    }

    private static function validateDate(string $nameInput, string $rule)
    {
        $format = 'Y-m-d';

        $value = self::searchInput($nameInput);
        $date = \DateTime::createFromFormat($format, $value);
        $errors = \DateTime::getLastErrors();
        if ($errors['error_count'] > 0 || $errors['warning_count'] > 0 || $date === false) {
            self::addError($nameInput, $rule, [$format]);
        }
    }

    private static function validateConfirm(string $nameInput, string $rule)
    {
        $value = self::searchInput($nameInput);
        $valueConfirm = self::searchInput($nameInput . '_confirm');
        if ($valueConfirm !== $value) {
            self::addError($nameInput, $rule);
        }
    }


    private static function validateMatches(string $nameInput, string $rule, $params)
    {
        $value = self::searchInput($nameInput);

        if (count($params) === 1) {
            $value2 = self::searchInput($params[0]);
            $name = $params[0];

            if ($value2 !== $value) {
                self::addError($nameInput, 'matches', [$name]);
            }
        } else {
            throw new \Exception("The max rule take only one parameter");
        }
    }


    private static function validateSlug(string $nameInput, string $rule)
    {
        //Entrada tipo slug aa-bb-cc
        $value = self::searchInput($nameInput);

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            self::addError($nameInput, $rule);
        }
    }

    private static function validateChoice(string $nameInput, string $rule, $params)
    {
        //busca que el numero sea lo que se dice en el controlador
        $value = self::searchInput($nameInput);
        if (count($params) <= 3) {
            if (!in_array($value, $params)) {
                $params = implode(', ', $params);
                self::addError($nameInput, 'choice', [$params]);
            }
        } else {
            throw new \Exception("The choice rule not be take except 3 paramaters");
        }
    }

    /**
     * revisar
     */
    private static function validateUnique(string $nameInput, string $rule, $params)
    {
        $value = self::searchInput($nameInput);

        if ($value !== '') {
            if (count($params) === 2) {
                $model = $params[0];
                $colum = $params[1];

                $class = "App\Model\\" . $model;
                $instance = new $class();
                $result = $instance->where($colum, $value)->get();

                if (!empty($result)) {
                    self::addError($nameInput, $rule);
                }
            }
        }
    }


    private static function validateNot_unique(string $nameInput, string $rule, $params)
    {
        $value = self::searchInput($nameInput);

        if ($value !== '') {
            if (count($params) === 2) {
                $model = $params[0];
                $colum = $params[1];

                $class = "App\Model\\" . $model;
                $instance = new $class();
                $result = $instance->where($colum, $value)->get();

                if (empty($result)) {
                    self::addError($nameInput, $rule);
                }
            }
        }
    }

    private static function validatePassword_verify(string $nameInput, string $rule, $params)
    {
        $value = self::searchInput($nameInput);

        if ($value !== '') {
            if (count($params) === 2) {
                $colum = $params[1];
                $model = $params[0];
                $email = self::searchInput($params[1]);

                $class = "App\Model\\" . $model;
                $instance = new $class();
                $result = $instance->where($colum, $email)->get();
                if (!empty($result)) {
                    $result = (object)$result;
                    if (!password_verify($value, $result->$nameInput)) {
                        self::addError($nameInput, $rule);
                    }
                }
            }
        }
    }


    private static function validateRequiredFile(string $nameInput, string $rule)
    {
        $value = self::searchInput($nameInput);

        if (empty($value['name']) || is_null($value['name'])) {
            self::addError($nameInput, $rule);
        }
    }


    private static function validateMaxSize(string $nameInput, string $rule, $params)
    {
        $value = self::searchInput($nameInput);

        if (count($params) === 1) {
            $max = (int)max($params) * 1048576;

            if (!is_null($max) && $value["size"] > $max) {
                self::addError($nameInput, 'maxSize', [(int)max($params)]);
            }
        } else {
            throw new \Exception("The max rule take only one parameter");
        }
    }


    private static function validateType(string $nameInput, string $rule, $params)
    {
        $value = self::searchInput($nameInput);
        $fileType = $value["type"];

        if ($fileType !== '') {
            $type = explode('/', $fileType);

            if (array_search($type[1], $params) === false) {

                self::addError($nameInput, 'type', [$value["name"]]);
            }
        }
    }
}
