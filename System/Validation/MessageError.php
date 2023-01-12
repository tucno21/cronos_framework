<?php

namespace Cronos\Validation;

class MessageError
{
    private $nameInput;

    private $rule;

    private $attributes;

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

    ];

    public function __construct(string $nameInput, string $rule, array $attributes = [])
    {
        $this->nameInput = $nameInput;
        $this->rule = $rule;
        $this->attributes = $attributes;
    }

    /**
     * establecer el mensaje de error de validación.
     * __toString() Este método debe devolver un string
     */
    public function __toString()
    {
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
