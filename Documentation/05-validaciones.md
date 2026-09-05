# Validaciones

> **AVISO IMPORTANTE PARA DESARROLLADORES E IAs**
>
> El validador de Cronos **NO es Laravel**. Las reglas tienen nombres parecidos a proposito, pero el comportamiento exacto (formatos, parametros, casos borde) es propio de Cronos y esta verificado con tests unitarios (ver `tests/Integration/ValidationTest.php` y `tests/Integration/ValidationDbRulesTest.php`, cobertura 31/31 reglas).
>
> **Si eres una IA: NO supongas comportamiento de Laravel. Basate UNICAMENTE en esta documentacion y en el codigo de `System/Validation/Validation.php`.**

El sistema de validacion permite validar datos desde los controladores usando el metodo `validate()` heredado de `Cronos\Http\Controller`, o de forma desacoplada y automatica mediante **Form Requests** (ver [`Documentation/15-form-requests.md`](15-form-requests.md)).

## Uso Basico

```php
public function register(Request $request, Hasher $hasher)
{
    $valid = $this->validate($request->all(), [
        'nombre'               => 'required|string|min:3|max:100',
        'correo'               => 'required|email|unique:Usuario,correo',
        'contrasena'           => 'required|min:6|max:50|matches:confirmar_contrasena',
        'confirmar_contrasena' => 'required',
    ]);

    if ($valid !== true) {
        return json(['status' => 'error', 'message' => $valid], 400);
    }

    // Si la validacion pasa (retorna true)
    Usuario::create($request->all());
}
```

**Comportamiento exacto de `validate()`:**

- Retorna `true` si TODO pasa.
- Retorna los errores si algo falla: como **objeto** en la aplicacion (la constante `RESULT_TYPE` vale `'object'`, definida en `System/Helpers/variable.php`) o como **array** en los tests.
- **NO lanza excepciones** por datos invalidos.
- Cada llamada a `validate()` empieza con la lista de errores vacia (los errores NO se acumulan entre llamadas).
- Con los inputs vacios retorna el string `'error'`.
- Las reglas de BD (`unique`, `not_unique`, `password_verify`) **ignoran silenciosamente** los campos ausentes o vacios: quien reporta el campo faltante es `required`.

## Tabla de Validaciones

| Regla | Que valida (comportamiento exacto) | Ejemplo |
|---|---|---|
| `required` | Campo presente y no vacio (un array con `name` vacio tambien falla) | `required` |
| `alpha` | SOLO letras unicode, sin espacios ni numeros | `alpha` |
| `alpha_dash` | Letras, numeros, guiones `-` y guiones bajos `_`. **NO permite espacios** | `alpha_dash` |
| `alpha_space` | Letras y espacios. NO numeros | `alpha_space` |
| `alpha_numeric` | Letras y numeros. NO espacios | `alpha_numeric` |
| `alpha_numeric_space` | Letras, numeros y espacios | `alpha_numeric_space` |
| `numeric` | Numero con signo opcional y punto decimal opcional: `-10`, `10.5` | `numeric` |
| `decimal` | Digitos con punto decimal opcional, sin signo: `10`, `10.5`. Rechaza coma `10,5` | `decimal` |
| `integer` | Entero con signo opcional: `-5`, `+5`, `5`. Rechaza `5.5` | `integer` |
| `is_natural` | Entero sin signo, incluye el `0` | `is_natural` |
| `is_natural_no_zero` | Entero sin signo **mayor que 0**, sin ceros a la izquierda | `is_natural_no_zero` |
| `email` | Formato de correo (`FILTER_VALIDATE_EMAIL`) | `email` |
| `url` | Formato de URL (`FILTER_VALIDATE_URL`) | `url` |
| `min:X` | Longitud **minima en caracteres** (`mb_strlen`, multibyte) | `min:3` |
| `max:X` | Longitud **maxima en caracteres** | `max:100` |
| `between:min,max` | Longitud **entre** min y max, inclusive (en caracteres) | `between:2,4` |
| `string` | Que el valor sea de tipo `string` (un `int` falla) | `string` |
| `text` | Texto que contenga al menos un espacio o simbolo despues de texto: `'hola mundo'` pasa, `'hola'` falla. Para texto libre prefiere `string|min:3` | `text` |
| `slug` | Formato slug: minusculas `a-z0-9` con guiones simples internos: `mi-post-1` | `slug` |
| `date` | Fecha exacta en formato **`Y-m-d`** (rechaza fechas imposibles como `2026-13-45`) | `date` |
| `datetime` | Fecha y hora exacta **`Y-m-d H:i:s`** | `datetime` |
| `time` | Hora exacta **`H:i:s`** (rechaza `25:99:99`) | `time` |
| `choice:valor1,valor2` | El valor debe estar DENTRO de la lista de valores permitidos (hasta 3) | `choice:admin,editor` |
| `confirm` | Compara el campo con el input `{campo}_confirm` | `confirm` |
| `matches:campo` | Compara el campo con OTRO input por nombre. Lanza excepcion si no recibe exactamente 1 parametro | `matches:correo` |

## Guia: Cuando Usar Cada Validacion

### Regla de oro sobre campos opcionales

En Cronos **cualquier regla aplicada a un campo lo vuelve obligatorio de facto**: si el campo llega vacio o no llega, la regla falla (las unicas excepciones son `unique`, `not_unique` y `password_verify`, que ignoran campos ausentes). Consecuencia practica:

- **Campo obligatorio**: declara sus reglas normalmente.
- **Campo opcional**: NO lo incluyas en `$rules`; validalo manualmente solo si llego:

```php
$data = $request->all();

if (!empty($data->telefono)) {
    //validacion manual del campo opcional
    if (!preg_match('/^[0-9\-\s]{7,20}$/', $data->telefono)) {
        return json(['status' => 'error', 'message' => 'Telefono invalido'], 400);
    }
}
```

### Recetas por tipo de campo

| Tipo de campo | Reglas recomendadas |
|---|---|
| Nombre / apellido | `required\|string\|min:3\|max:100` |
| Correo electronico | `required\|email\|max:191` (+ `unique:Usuario,correo` en registro) |
| Contrasena | `required\|min:6\|max:50` (+ `matches:confirmar_contrasena` en registro) |
| Confirmacion de contrasena | `required` (la comparacion la hace `matches` del campo principal) |
| Usuario / nick | `required\|alpha_numeric\|min:3\|max:50` |
| Telefono | `required\|numeric` (o `alpha_dash` si incluye guiones) |
| Edad / cantidad | `required\|is_natural` (o `is_natural_no_zero` si no admite 0) |
| Precio / monto | `required\|decimal` |
| Slug (URL amigable) | `required\|slug` (+ `unique:Publicacion,slug`) |
| Fecha (cumpleanos, vencimiento) | `required\|date` |
| Fecha y hora (publicacion) | `required\|datetime` |
| Hora | `required\|time` |
| Titulo corto | `required\|string\|min:3\|max:255` |
| Contenido largo / descripcion | `required\|string\|min:3` |
| Rol / estado / categoria fija | `required\|choice:admin,editor,usuario` |
| Sitio web | `required\|url` |
| Direccion | `required\|alpha_numeric_space\|min:5\|max:255` |
| Codigo / identificador alfanumerico | `required\|alpha_dash` |
| Archivo de imagen | `required_file\|max_size:2\|type:png,jpg` |

### Ejemplos de escenarios completos

**Registro de usuario (API):**

```php
$valid = $this->validate($request->all(), [
    'nombre'               => 'required|string|min:3|max:100',
    'correo'               => 'required|email|max:191|unique:Usuario,correo',
    'contrasena'           => 'required|min:6|max:50|matches:confirmar_contrasena',
    'confirmar_contrasena' => 'required',
]);
```

**Login (API):**

```php
$valid = $this->validate($request->all(), [
    'correo'     => 'required|email|not_unique:Usuario,correo',
    'contrasena' => 'required|password_verify:Usuario,correo',
]);
```

**Perfil con foto (web, con archivo):**

```php
$valid = $this->validate($request->all(), [
    'biografia'         => 'required|string|min:10',
    'fecha_nacimiento'  => 'required|date',
    'sitio_web'         => 'required|url',
    'avatar'            => 'required_file|max_size:2|type:png,jpg',
]);

if ($valid !== true) {
    return back()->withErrors($request->all(), $valid);
}
```

**Creacion de publicacion con estado controlado:**

```php
$valid = $this->validate($request->all(), [
    'titulo'    => 'required|string|min:3|max:255',
    'slug'      => 'required|slug|unique:Publicacion,slug',
    'contenido' => 'required|string|min:3',
    'estado'    => 'required|choice:borrador,publicado,archivado',
]);
```

## Reglas que Consultan la Base de Datos

Estas 3 reglas requieren que el modelo exista en `App/Models/` (carpeta raiz, sin subcarpetas) y que la conexion de BD este configurada.

### unique

El valor NO debe existir ya en la BD (tipico de registro):

```php
'correo' => 'required|email|unique:Usuario,correo',
```

- `Usuario` es el **nombre corto del modelo** (resuelve a `App\Models\Usuario`).
- `correo` es la columna de la tabla (en el esquema en espanol).
- Si el correo ya existe agrega el error "El correo ya existe."

### not_unique

El valor DEBE existir en la BD (tipico de login, antes de verificar la contrasena):

```php
'correo' => 'required|email|not_unique:Usuario,correo',
```

- Si el correo NO existe agrega el error "El correo no existe en la BD."

### password_verify

Verifica que el valor del campo coincida con el hash guardado en la BD:

```php
'contrasena' => 'required|password_verify:Usuario,correo',
```

- Busca el registro usando el valor del input indicado en `column` (aqui `correo`) y compara el campo actual (`contrasena`) contra el hash con `password_verify()` de PHP.
- Si el registro no existe la regla NO agrega error (ese caso lo cubre `not_unique`).
- El hash debe estar generado con `password_hash()` de PHP (el proyecto usa `Hasher`/Bcrypt).

## Validaciones para Archivos

El valor del campo debe ser el array estilo `$_FILES` (con claves `name`, `size`, `type`):

| Regla | Que valida (comportamiento exacto) | Ejemplo |
|---|---|---|
| `required_file` | El archivo llego (clave `name` no vacia) | `required_file` |
| `max_size:X` | Tamanio maximo **en MEGABYTES** (`X * 1048576` bytes) | `max_size:2` |
| `type:ext1,ext2` | El subtipo del MIME esta en la lista: `image/png` compara contra `png` | `type:png,jpg` |

Ejemplo completo (el input `avatar` debe contener el array del archivo subido):

```php
$valid = $this->validate($request->all(), [
    'avatar' => 'required_file|max_size:2|type:png,jpg',
]);
```

## Mensajes de Error

Los mensajes provienen de `System/Validation/MessageError.php`. Ejemplos reales:

- `required` -> "El campo correo es obligatorio"
- `unique` -> "El correo ya existe."
- `not_unique` -> "El correo no existe en la BD."

## Mostrar Errores en las Vistas

Si se uso `return back()->withErrors($request->all(), $valid)` en el controlador, en la vista se pueden usar:

```php
// Saber si existe error en un campo
ifError('correo')

// Imprimir el error
<?= error('correo') ?>

// Mantener el valor anterior al recargar
<?= old('correo') ?>
```

### Ejemplo Completo en una Vista

```html
<div class="mb-3">
    <label class="form-label">Correo</label>
    <input
        name="correo"
        type="text"
        class="form-control <?= ifError('correo') ? 'is-invalid' : '' ?>"
        value="<?= old('correo') ?>"
    />

    <?php if (ifError('correo')) : ?>
    <div class="invalid-feedback">
        <?= error('correo') ?>
    </div>
    <?php endif; ?>
</div>
```

Tambien se puede usar la directiva `@error` en las vistas (ver [06-vistas.md](06-vistas.md)).

## Errores en APIs (JSON)

En controladores de API el patron habitual es devolver los errores en el JSON:

```php
if ($valid !== true) {
    return json(['status' => 'error', 'message' => $valid], 400);
}
```

## Cobertura de Tests

Las 31 reglas tienen pruebas automaticas (paso y fallo):

- `tests/Integration/ValidationTest.php` — reglas sin BD y reglas de archivos.
- `tests/Integration/ValidationDbRulesTest.php` — `unique`, `not_unique` y `password_verify` contra la BD real (se marcan `skipped` si MySQL no esta disponible).

---

> **Anterior**: [04 - Modelos](04-modelos.md)
> **Siguiente**: [06 - Vistas](06-vistas.md)
