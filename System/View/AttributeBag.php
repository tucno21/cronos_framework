<?php

namespace Cronos\View;

/**
 * bolsa de atributos no declarados de un componente ($attributes).
 * los valores se escapan al renderizar.
 */
class AttributeBag
{
    public function __construct(protected array $attributes = [])
    {
    }

    /**
     * combina atributos por defecto con los recibidos y retorna una bolsa
     * renderizable (class y style se concatenan; el resto: el recibido gana)
     */
    public function merge(array $defaultAttributes): self
    {
        $merged = $defaultAttributes;

        foreach ($this->attributes as $key => $value) {
            if (in_array($key, ['class', 'style'], true) && isset($merged[$key])) {
                $merged[$key] = trim($merged[$key] . ' ' . $value);
                continue;
            }

            $merged[$key] = $value;
        }

        return new self($merged);
    }

    /**
     * clases condicionales unidas a la clase actual del componente.
     * acepta ['clase'] o ['clase' => condicion]
     */
    public function class(array $classes): string
    {
        $own = (string) ($this->attributes['class'] ?? '');
        $extra = self::conditionalClasses($classes);

        return trim($own . ' ' . $extra);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function only(array $keys): self
    {
        return new self(array_intersect_key($this->attributes, array_flip($keys)));
    }

    public function except(array $keys): self
    {
        return new self(array_diff_key($this->attributes, array_flip($keys)));
    }

    public function all(): array
    {
        return $this->attributes;
    }

    public function __toString(): string
    {
        return trim($this->renderAttributes($this->attributes));
    }

    /**
     * salida HTML sin escapar; permite usar {{ $attributes }} en plantillas
     */
    public function toHtml(): string
    {
        return trim($this->renderAttributes($this->attributes));
    }

    /**
     * filtra clases condicionales: ['a', 'b' => true, 'c' => false] => 'a b'
     */
    public static function conditionalClasses(array $classes): string
    {
        $result = [];

        foreach ($classes as $key => $value) {
            if (is_int($key)) {
                $result[] = (string) $value;
                continue;
            }

            if ($value) {
                $result[] = $key;
            }
        }

        return implode(' ', $result);
    }

    private function renderAttributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $html .= ' ' . $key;
                continue;
            }

            if ($value === false || $value === null) {
                continue;
            }

            $html .= ' ' . $key . '="' . e($value) . '"';
        }

        return $html;
    }
}
