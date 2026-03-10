<?php

namespace Cronos\Validation;

class MessageError
{
    private $nameInput;

    private $rule;

    private $attributes;

    private $customMessage;

    private $messages = [
        'alpha'             => 'El campo %s solo debe contener caracteres alfabéticos sin espacio.',
        'alpha_space'       => 'El campo %s solo debe contener caracteres alfabéticos y espacios.',
        'alpha_dash'        => 'El campo %s solo debe contener caracteres alfanuméricos, guiones bajos y guiones sin espacio.',
        'alpha_numeric'     => 'El campo %s solo debe contener caracteres alfanuméricos sin espacio.',
        'alpha_numeric_space'     => 'El campo %s solo debe contener caracteres alfanuméricos y de espacio.',
        'decimal'     => 'El campo %s debe contener un número decimal.',
        'integer'           => 'El campo %s debe contener un número entero.',
        'is_natural'           => 'El campo %s solo debe contener numeros naturales.',
        'is_natural_no_zero'           => 'El campo %s solo debe contener numeros naturales y debe ser mayor que cero',
        'numeric'           => 'El campo %s debe contener solo números.',
        'required'          => 'El campo %s es obligatorio',
        'email'             => "El campo %s no es un valido",
        'url'             => "El campo %s debe contener una URL válida.",
        'min'               => 'El campo %s debe tener al menos %d caracteres de longitud.',
        'max'               => 'El campo %s no puede exceder los %d caracteres de longitud.',
        'string'               => 'El campo %s debe ser una cadena válida.',
        'confirm'           => 'Los campos %s no son iguales',
        'slug'              => 'El campo %s no es una slug valido',
        'text'              => "El campo %s no es un texto valido",
        'choice'            => 'El valor del campo %s debe estar en esta lista (%s)',
        'between'           => 'El campo %s debe contener entre %d a %d caracteres',
        'datetime'          => 'El campo %s debe ser una fecha y hora valido',
        'time'              => 'El campo %s debe ser una hora valido',
        'date'              => 'El campo %s debe ser una fecha valido',
        'matches'              => 'El campo %s no coincide con (%s)',
        'unique'              => 'El %s ya existe.',
        'not_unique'              => 'El %s no existe en la BD.',

        'requiredFile'              => 'El archivo %s es obligatorio.',
        'maxSize'              => 'El archivo %s a sobrepasado %d MB.',
        'type'              => 'El campo %s tiene un archivo no valido (%s)',
        // 'file'              => 'El campo %s debe ser un archivo valido',
        'password_verify'    => 'Error la contraseña no coincide',

        // ===== NUEVOS MENSAJES (FASE 3) =====
        'boolean'           => 'El campo %s debe ser verdadero o falso.',
        'date_format'       => 'El campo %s no coincide con el formato %s.',
        'before'            => 'El campo %s debe ser una fecha anterior a %s.',
        'after'             => 'El campo %s debe ser una fecha posterior a %s.',
        'in'                => 'El campo %s debe ser uno de los siguientes: %s.',
        'not_in'            => 'El campo %s no debe ser ninguno de los siguientes: %s.',
        'same'              => 'El campo %s debe coincidir con %s.',
        'different'         => 'El campo %s debe ser diferente de %s.',
        'starts_with'       => 'El campo %s debe comenzar con %s.',
        'ends_with'         => 'El campo %s debe terminar con %s.',
        'regex'             => 'El formato del campo %s es inválido.',
        'file'              => 'El campo %s debe ser un archivo válido.',
        'image'             => 'El campo %s debe ser una imagen (jpg, png, gif, webp, svg).',
        'mimes'             => 'El campo %s debe ser un archivo de tipo: %s.',
        'max_size'          => 'El campo %s no puede superar los %s KB.',
        'nullable'          => '', // No tiene mensaje, es una regla especial
        'sometimes'         => '', // No tiene mensaje, es una regla especial

    ];

    /**
     * Constructor de MessageError
     * 
     * @param string $nameInput Nombre del campo
     * @param string $rule Nombre de la regla
     * @param array $attributes Atributos para el mensaje
     * @param string|null $customMessage Mensaje personalizado (opcional)
     */
    public function __construct(string $nameInput, string $rule, array $attributes = [], ?string $customMessage = null)
    {
        $this->nameInput = $nameInput;
        $this->rule = $rule;
        $this->attributes = $attributes;
        $this->customMessage = $customMessage;
    }

    /**
     * establecer el mensaje de error de validación.
     * __toString() Este método debe devolver un string
     */
    public function __toString()
    {
        // Si hay un mensaje personalizado, usarlo
        if ($this->customMessage !== null) {
            // Reemplazar :attribute con el nombre del campo
            $message = str_replace(':attribute', $this->nameInput, $this->customMessage);

            // Reemplazar otros parámetros :param1, :param2, etc.
            foreach ($this->attributes as $index => $value) {
                $message = str_replace(":param" . ($index + 1), $value, $message);
            }

            return $message;
        }

        //busca si la regla existe en el array messages[]
        if (!array_key_exists($this->rule, $this->messages)) {
            return "Los campos {$this->nameInput} no coincide con la regla {$this->rule}";
        } else {
            //array_merge : Combina dos o más arrays los valores de uno se anexan al final
            $params = array_merge([$this->messages[$this->rule], $this->nameInput], $this->attributes);
            //invocar a la funcion sprintf
            //sprintf : Devuelve un string formateado con valores nameInput, attributes
            //$num = 5;
            //$ubicación = 'árbol';
            //$formato = 'Hay %d monos en el %s';
            //echo sprintf($formato, $num, $ubicación)      //Hay 5 monos en el árbol
            return (string)call_user_func_array('sprintf', $params);
        }
    }
}
