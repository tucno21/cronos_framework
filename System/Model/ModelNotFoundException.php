<?php

namespace Cronos\Model;

/**
 * Lanzada por findOrFail() y firstOrFail() cuando la consulta no
 * devuelve resultados (equivalente a ModelNotFoundException de Laravel).
 */
class ModelNotFoundException extends \RuntimeException
{
}
