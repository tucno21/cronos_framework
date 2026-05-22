# Validaciones

El sistema de validacion permite validar datos de formularios desde los controladores usando el metodo `validate()` heredado de `Cronos\Http\Controller`.

## Uso Basico

```php
public function register(Request $request)
{
    $valid = $this->validate($request->all(), [
        'name' => 'required|alpha',
        'username' => 'required|alpha_numeric',
        'email' => 'required|email|unique:HomeModel,email',
        'password' => 'required|min:3|max:12|matches:password_confirm',
        'password_confirm' => 'required',
        'photo' => 'requiredFile|maxSize:2|type:jpeg,png,zip,svg+xml',
    ]);

    if ($valid !== true) {
        return back()->withErrors($request->all(), $valid);
    }

    // Si la validacion es correcta
    $request->input('email');
    User::create($request->all());
    return redirect()->route('login');
}
```

> **Nota**: `validate()` retorna `true` si pasa, o un array de errores si falla. No lanza excepciones.

## Tabla de Validaciones

| Validacion | Descripcion | Ejemplo |
|---|---|---|
| `alpha` | Solo letras | `alpha` |
| `alpha_space` | Solo letras y espacios | `alpha_space` |
| `alpha_dash` | Solo letras, espacios y guiones | `alpha_dash` |
| `alpha_numeric` | Solo letras y numeros | `alpha_numeric` |
| `decimal` | Solo numeros decimales | `decimal` |
| `integer` | Solo numeros enteros | `integer` |
| `is_natural` | Solo numeros naturales | `is_natural` |
| `is_natural_no_zero` | Solo numeros naturales sin cero | `is_natural_no_zero` |
| `numeric` | Solo numeros | `numeric` |
| `required` | Requerido, obligatorio | `required` |
| `email` | Correo electronico | `email` |
| `url` | Texto tipo URL | `url` |
| `min:number` | Minimo de caracteres | `min:5` |
| `max:number` | Maximo de caracteres | `max:5` |
| `string` | Solo texto | `string` |
| `confirm` | Comparar dos inputs iguales (agregar `_confirm` al segundo) | `confirm` |
| `slug` | Texto tipo slug **aa-bb-cc** | `slug` |
| `text` | Solo texto | `text` |
| `choice:param` | El valor debe ser igual a param | `choice:table` |
| `between:min,max` | Entre minimo y maximo de caracteres | `between:1,5` |
| `datetime` | Fecha y hora **Y-m-d H:i:s** | `datetime` |
| `time` | Hora **H:i:s** | `time` |
| `date` | Fecha **Y-m-d** | `date` |
| `matches:2inputs` | Comparar dos inputs | `matches:otro_input` |
| `unique:model,column` | Unico en la tabla model y columna column | `unique:User,email` |
| `not_unique:model,column` | No unico en la tabla model y columna column | `not_unique:User,email` |
| `password_verify:model,column` | Verificar contrasena en la tabla | `password_verify:User,email` |

## Validaciones para Archivos

| Validacion | Descripcion | Ejemplo |
|---|---|---|
| `requiredFile` | Archivo requerido | `requiredFile` |
| `maxSize:number` | Tamanio maximo en bytes | `maxSize:1000` |
| `type:param` | Tipo de archivo | `type:jpg,png` |

## Consideraciones

### unique y not_unique

La palabra `model` debe ser exactamente igual al nombre del modelo: `User`. La columna debe ser exactamente igual al nombre de la columna en la base de datos: `email`.

```php
'email' => 'required|email|unique:User,email',
```

Los modelos referenciados deben estar en la carpeta `App/Models/` (no en subcarpetas).

### password_verify

```php
'password' => 'required|password_verify:User,email',
```

La palabra `model` es el nombre del modelo (`User`) y la palabra `column` esta relacionada al input `email`. El sistema busca el valor del input `email` en la base de datos y compara con el valor del input `password`.

## Mostrar Errores en las Vistas

Si se uso `return back()->withErrors($dataInput, $errors)` en el controlador, en la vista se pueden usar:

```php
// Saber si existe error en un campo
ifError('name')

// Imprimir el error
<?= error('name') ?>

// Mantener el valor anterior al recargar
<?= old('name') ?>
```

### Ejemplo Completo en una Vista

```html
<div class="mb-3">
    <label class="form-label">Correo</label>
    <input
        name="email"
        type="text"
        class="form-control <?= ifError('email') ? 'is-invalid' : '' ?>"
        value="<?= old('email') ?>"
    />

    <?php if (ifError('email')) : ?>
    <div class="invalid-feedback">
        <?= error('email') ?>
    </div>
    <?php endif; ?>
</div>
```

Tambien se puede usar la directiva `@error` en las vistas (ver [06-vistas.md](06-vistas.md)).

---

> **Anterior**: [04 - Modelos](04-modelos.md)
> **Siguiente**: [06 - Vistas](06-vistas.md)
