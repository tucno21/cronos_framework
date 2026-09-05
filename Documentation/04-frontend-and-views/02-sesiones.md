# Sesiones

La session se gestiona mediante el helper `session()` disponible globalmente. El sistema usa sesiones nativas de PHP con soporte para datos flash y persistentes.

## Sesion Basica

```php
// Crear una session
session()->set('nombre', 'valor');
session()->put('nombre', 'valor');

// Agregar un valor a una session existente
session()->push('nombre', 'valor');

// Session flash (se elimina al recargar la pagina)
session()->flash('nombre', 'valor');

// Verificar si existe una session (retorna true o false)
session()->has('nombre');

// Obtener el valor de una session
session()->get('nombre');

// Obtener el valor y eliminarla
session()->pull('nombre');

// Eliminar una session
session()->forget('nombre');
session()->remove('nombre');

// Eliminar todas las sessiones
session()->flush();
```

## Sesion de Autenticacion (sin clave)

```php
// Crear session de usuario (recibe objeto/array de datos del usuario)
session()->attempt($data);

// Consultar si existe sesion de usuario
session()->hasUser();

// Obtener los datos del usuario
session()->user();

// Eliminar la sesion de usuario (logout)
session()->logout();
```

## Ejemplo de Uso en Controlador

### Login

```php
public function login(Request $request)
{
    $valid = $this->validate($request->all(), [
        'email' => 'required|email',
        'password' => 'required|password_verify:User,email',
    ]);

    if ($valid !== true) {
        return back()->withErrors($request->all(), $valid);
    }

    $user = User::where('email', $request->email)->first();
    session()->attempt($user);

    return redirect()->route('dashboard.index');
}
```

### Logout

```php
public function logout()
{
    session()->logout();
    return redirect()->route('home.index');
}
```

### Verificar Autenticacion

```php
// En un controlador
if (!session()->hasUser()) {
    return redirect()->route('login.index');
}

$user = session()->user();
```

## Mensajes Flash

Los mensajes flash son utiles para notificaciones que solo deben mostrarse una vez:

```php
// En el controlador (despues de una accion exitosa)
return redirect()->route('dashboard.index')->with('message', 'Registro exitoso');

// En la vista
<?php if (session()->has('message')): ?>
    <div class="alert alert-success">
        <?= session()->get('message') ?>
    </div>
<?php endif; ?>
```

## Errores de Validacion en Sesion

El sistema guarda automaticamente errores y datos anteriores en la sesion cuando se usa `back()->withErrors()`:

```php
// En el controlador
return back()->withErrors($request->all(), $errors);

// En la vista - verificar si hay error
ifError('email');

// En la vista - mostrar error
<?= error('email') ?>

// En la vista - recuperar valor anterior
<?= old('email') ?>
```

## Notas Importantes

- La sesion se inicia automaticamente en `Cronos\App::setSessionHandler()`. No es necesario llamar `session_start()`.
- Los datos flash se "envejecen" automaticamente: new → old → eliminado entre peticiones.
- La sesion usa claves internas (`_flash`, `_cronos_previous_path`, `_errors_inputs`). No sobreescribir estas claves.
- Solo soporta el driver `native` (sesiones nativas de PHP).

---

> **Anterior**: [06 - Vistas](06-vistas.md)
> **Siguiente**: [08 - Middleware](08-middleware.md)
