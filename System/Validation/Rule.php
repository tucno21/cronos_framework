<?php

namespace Cronos\Validation;

/**
 * Clase Rule para validación con interfaz fluida
 * 
 * Permite construir reglas de validación de forma programática
 * con una sintaxis encadenada más legible.
 * 
 * Ejemplo de uso:
 * 
 * // Validación con Rule object
 * 'email' => Rule::required()->email()->unique('User', 'email')->max(100),
 * 'avatar' => Rule::nullable()->image()->max_size(2048),
 * 'status' => Rule::required()->in('active', 'inactive', 'pending'),
 * 'website' => Rule::nullable()->url()->starts_with('https://'),
 * 
 * // También se pueden usar métodos estáticos individuales
 * Rule::required()
 * Rule::email()
 * Rule::max(100)
 * 
 * // Y luego combinarlos manualmente con join('|')
 * $rules = implode('|', [Rule::required(), Rule::email(), Rule::max(100)]);
 */
class Rule
{
    /**
     * Reglas acumuladas
     */
    private array $rules = [];

    /**
     * Constructor privado para prevenir instanciación directa
     * Use los métodos estáticos para crear reglas
     */
    private function __construct() {}

    /**
     * Crear nueva instancia de Rule
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Agregar una regla personalizada
     */
    public function rule(string $rule): self
    {
        $this->rules[] = $rule;
        return $this;
    }

    /**
     * El campo es obligatorio
     */
    public static function required(): self
    {
        $instance = new self();
        $instance->rules[] = 'required';
        return $instance;
    }

    /**
     * El campo puede ser null o vacío
     * Detiene la validación del campo si está vacío
     */
    public static function nullable(): self
    {
        $instance = new self();
        $instance->rules[] = 'nullable';
        return $instance;
    }

    /**
     * Validar solo si el campo está presente en el request
     */
    public static function sometimes(): self
    {
        $instance = new self();
        $instance->rules[] = 'sometimes';
        return $instance;
    }

    /**
     * Validar formato de email
     */
    public function email(): self
    {
        $this->rules[] = 'email';
        return $this;
    }

    /**
     * Validar formato de URL
     */
    public function url(): self
    {
        $this->rules[] = 'url';
        return $this;
    }

    /**
     * Validar que sea solo números (enteros o decimales)
     */
    public static function numeric(): self
    {
        $instance = new self();
        $instance->rules[] = 'numeric';
        return $instance;
    }

    /**
     * Validar que sea solo enteros
     */
    public static function integer(): self
    {
        $instance = new self();
        $instance->rules[] = 'integer';
        return $instance;
    }

    /**
     * Validar que sea booleano
     * Acepta: true, false, 1, 0, "1", "0"
     */
    public static function boolean(): self
    {
        $instance = new self();
        $instance->rules[] = 'boolean';
        return $instance;
    }

    /**
     * Validar formato de fecha (Y-m-d)
     */
    public static function date(): self
    {
        $instance = new self();
        $instance->rules[] = 'date';
        return $instance;
    }

    /**
     * Validar fecha contra formato específico
     * 
     * @param string $format Formato de fecha (ej: d/m/Y, H:i)
     */
    public function date_format(string $format): self
    {
        $this->rules[] = "date_format:{$format}";
        return $this;
    }

    /**
     * Validar que la fecha sea anterior a la dada
     * 
     * @param string $date Fecha límite (ej: 2024-12-31)
     */
    public function before(string $date): self
    {
        $this->rules[] = "before:{$date}";
        return $this;
    }

    /**
     * Validar que la fecha sea posterior a la dada
     * 
     * @param string $date Fecha mínima (ej: 2024-01-01)
     */
    public function after(string $date): self
    {
        $this->rules[] = "after:{$date}";
        return $this;
    }

    /**
     * Validar longitud mínima
     * 
     * @param int $length Longitud mínima
     */
    public function min(int $length): self
    {
        $this->rules[] = "min:{$length}";
        return $this;
    }

    /**
     * Validar longitud máxima
     * 
     * @param int $length Longitud máxima
     */
    public function max(int $length): self
    {
        $this->rules[] = "max:{$length}";
        return $this;
    }

    /**
     * Validar longitud entre dos valores
     * 
     * @param int $min Longitud mínima
     * @param int $max Longitud máxima
     */
    public function between(int $min, int $max): self
    {
        $this->rules[] = "between:{$min},{$max}";
        return $this;
    }

    /**
     * Validar que el valor esté en la lista
     * 
     * @param string ...$values Valores permitidos
     */
    public function in(string ...$values): self
    {
        $allowed = implode(',', $values);
        $this->rules[] = "in:{$allowed}";
        return $this;
    }

    /**
     * Validar que el valor NO esté en la lista
     * 
     * @param string ...$values Valores prohibidos
     */
    public function not_in(string ...$values): self
    {
        $notAllowed = implode(',', $values);
        $this->rules[] = "not_in:{$notAllowed}";
        return $this;
    }

    /**
     * Validar que exista campo de confirmación
     * Busca campo_campo con el mismo valor
     */
    public function confirmed(): self
    {
        $this->rules[] = 'confirmed';
        return $this;
    }

    /**
     * Validar que sea igual a otro campo
     * 
     * @param string $field Nombre del campo a comparar
     */
    public function same(string $field): self
    {
        $this->rules[] = "same:{$field}";
        return $this;
    }

    /**
     * Validar que sea diferente de otro campo
     * 
     * @param string $field Nombre del campo a comparar
     */
    public function different(string $field): self
    {
        $this->rules[] = "different:{$field}";
        return $this;
    }

    /**
     * Validar que el string empiece con el prefijo
     * 
     * @param string $prefix Prefijo requerido
     */
    public function starts_with(string $prefix): self
    {
        $this->rules[] = "starts_with:{$prefix}";
        return $this;
    }

    /**
     * Validar que el string termine con el sufijo
     * 
     * @param string $suffix Sufijo requerido
     */
    public function ends_with(string $suffix): self
    {
        $this->rules[] = "ends_with:{$suffix}";
        return $this;
    }

    /**
     * Validar contra expresión regular
     * 
     * @param string $pattern Patrón de expresión regular
     */
    public function regex(string $pattern): self
    {
        $this->rules[] = "regex:{$pattern}";
        return $this;
    }

    /**
     * Validar que sea un archivo subido
     */
    public static function file(): self
    {
        $instance = new self();
        $instance->rules[] = 'file';
        return $instance;
    }

    /**
     * Validar que el archivo sea una imagen
     * Extensiones: jpg, jpeg, png, gif, webp, svg
     */
    public function image(): self
    {
        $this->rules[] = 'image';
        return $this;
    }

    /**
     * Validar extensiones de archivo permitidas
     * 
     * @param string ...$extensions Extensiones permitidas
     */
    public function mimes(string ...$extensions): self
    {
        $allowed = implode(',', $extensions);
        $this->rules[] = "mimes:{$allowed}";
        return $this;
    }

    /**
     * Validar tamaño máximo de archivo en KB
     * 
     * @param int $size Tamaño máximo en KB
     */
    public function max_size(int $size): self
    {
        $this->rules[] = "max_size:{$size}";
        return $this;
    }

    /**
     * Validar que sea único en la base de datos
     * 
     * @param string $model Nombre del modelo
     * @param string $column Nombre de la columna
     */
    public function unique(string $model, string $column): self
    {
        $this->rules[] = "unique:{$model},{$column}";
        return $this;
    }

    /**
     * Validar que existe en la base de datos
     * 
     * @param string $model Nombre del modelo
     * @param string $column Nombre de la columna
     */
    public function exists(string $model, string $column): self
    {
        $this->rules[] = "not_unique:{$model},{$column}";
        return $this;
    }

    /**
     * Validar solo letras alfabéticas sin espacio
     */
    public static function alpha(): self
    {
        $instance = new self();
        $instance->rules[] = 'alpha';
        return $instance;
    }

    /**
     * Validar alfanuméricos, guiones y guiones bajos
     */
    public static function alpha_dash(): self
    {
        $instance = new self();
        $instance->rules[] = 'alpha_dash';
        return $instance;
    }

    /**
     * Validar solo letras y espacios
     */
    public static function alpha_space(): self
    {
        $instance = new self();
        $instance->rules[] = 'alpha_space';
        return $instance;
    }

    /**
     * Validar solo alfanuméricos sin espacio
     */
    public static function alpha_numeric(): self
    {
        $instance = new self();
        $instance->rules[] = 'alpha_numeric';
        return $instance;
    }

    /**
     * Validar solo string
     */
    public static function string(): self
    {
        $instance = new self();
        $instance->rules[] = 'string';
        return $instance;
    }

    /**
     * Validar que sea un slug válido
     */
    public static function slug(): self
    {
        $instance = new self();
        $instance->rules[] = 'slug';
        return $instance;
    }

    /**
     * Convertir las reglas a string
     * Retorna las reglas separadas por |
     * 
     * @return string
     */
    public function __toString()
    {
        return implode('|', $this->rules);
    }

    /**
     * Obtener las reglas como array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return $this->rules;
    }
}
