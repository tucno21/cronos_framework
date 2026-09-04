<?php

namespace Cronos\View\Compiler;

class BlockMatcher
{
    /**
     * localizar un bloque @open ... @close balanceado en el contenido.
     *
     * a diferencia de un regex lazy, cuenta la profundidad de aperturas
     * intermedias para encontrar el cierre correcto, lo que permite
     * directivas anidadas del mismo tipo (@foreach dentro de @foreach).
     *
     * @return array{start:int, openTokenEnd:int, closeStart:int, end:int, body:string}|null
     *         start:       posicion donde inicia "@open"
     *         openTokenEnd: posicion inmediatamente despues de "@open"
     *         closeStart:  posicion donde inicia "@close"
     *         end:         posicion inmediatamente despues de "@close"
     *         body:        contenido entre ambos tokens
     */
    public static function find(string $content, string $open, string $close, int $from = 0): ?array
    {
        $pattern = '/@(' . preg_quote($open, '/') . '|' . preg_quote($close, '/') . ')(?![\w-])/';

        if (!preg_match($pattern, $content, $first, PREG_OFFSET_CAPTURE, $from)) {
            return null;
        }

        //el primer token encontrado debe ser la apertura
        if ($first[1][0] !== $open) {
            return null;
        }

        $start = $first[0][1];
        $openTokenEnd = $start + strlen($first[0][0]);

        //la apertura ya fue consumida: el primer token del body cierra o anida
        $depth = 1;
        $pos = $openTokenEnd;

        while (preg_match($pattern, $content, $token, PREG_OFFSET_CAPTURE, $pos)) {
            $name = $token[1][0];
            $tokenStart = $token[0][1];
            $tokenEnd = $tokenStart + strlen($token[0][0]);

            if ($name === $open) {
                $depth++;
                $pos = $tokenEnd;
                continue;
            }

            $depth--;

            if ($depth === 0) {
                return [
                    'start' => $start,
                    'openTokenEnd' => $openTokenEnd,
                    'closeStart' => $tokenStart,
                    'end' => $tokenEnd,
                    'body' => substr($content, $openTokenEnd, $tokenStart - $openTokenEnd),
                ];
            }

            $pos = $tokenEnd;
        }

        return null;
    }
}
