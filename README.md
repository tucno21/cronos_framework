# CRONOS FRAMEWORK PHP 8.2

## Requisitos

- PHP >= 8.2
- Composer

## Instalacion

1. Clonar el repositorio
2. Ejecutar `composer install`
3. Crear un archivo `.env` en la raiz del proyecto
4. Configurar el `.env` con los datos de la base de datos

## Estructura de Carpetas

```
📁 cronos_framework/
├── 📁 App/
│   ├── 📁 Controllers/
│   ├── 📁 Middlewares/
│   ├── 📁 Models/
│   ├── 📁 Migrations/
│   ├── 📁 Providers/
│   ├── 📁 Help/
│   └── 📁 Library/
├── 📁 config/
├── 📁 public/
├── 📁 resources/
├── 📁 routes/
├── 📁 System/
├── 📁 storage/
└── 📄 cronos (CLI)
```

## Documentacion

La documentacion completa esta organizada en la carpeta `Documentation/`:

| Archivo | Descripcion |
|---|---|
| [00 - Instalacion](Documentation/00-instalacion.md) | Requisitos, instalacion, estructura de carpetas, flujo de ejecucion |
| [01 - Rutas](Documentation/01-rutas.md) | Rutas web, API, parametros, grupos, middlewares en rutas |
| [02 - Consola](Documentation/02-consola.md) | Comandos CLI: controller, model, middleware, migrate |
| [03 - Controladores](Documentation/03-controladores.md) | Estructura, Request, Response, middleware en constructor |
| [04 - Modelos](Documentation/04-modelos.md) | Configuracion, CRUD, query builder, relaciones, ModelCollection |
| [05 - Validaciones](Documentation/05-validaciones.md) | Reglas, tabla completa, validacion de archivos |
| [06 - Vistas](Documentation/06-vistas.md) | Directivas CronosEngine, layouts, componentes x-, slots, cache |
| [07 - Sesiones](Documentation/07-sesiones.md) | Sesion basica, autenticacion, flash, errores de validacion |
| [08 - Middleware](Documentation/08-middleware.md) | Tipos, Pipeline, Throttle, Log, CORS |
| [09 - Helpers](Documentation/09-helpers.md) | Depuracion, HTTP, archivos/imagenes, constantes |
| [10 - Configuracion](Documentation/10-configuracion.md) | Variables .env, archivos config/* |
| [11 - Guia Nuevo Modulo](Documentation/11-guia-nuevo-modulo.md) | Paso a paso: modelo, migracion, controlador, vistas, rutas |

## Inicio Rapido

```bash
# Crear un controlador
php cronos make:controller UserController

# Crear un modelo
php cronos make:model User

# Crear un middleware
php cronos make:middleware AuthMiddleware

# Crear y ejecutar migracion
php cronos make:migration database
php cronos migrate
```

## Creditos

_Modelo de framework php_

- [Juan de la Torre](https://www.udemy.com/course/desarrollo-web-completo-con-html5-css3-js-php-y-mysql/) - Curso PHP y base framework.
- [The Codeholic](https://www.youtube.com/playlist?list=PLLQuc_7jk__Uk_QnJMPndbdKECcTEwTA1) - Framework php.
- [Antonio Sarosi](https://antoniosarosi.com/) - Framework php.

_Modificacion para la validacion del formulario de_

- [mkakpabla](https://github.com/mkakpabla/form-validation-php#readme) - Validacion Adaptado.
- [booomerang](https://github.com/booomerang/Validatr/tree/master/src) - Validacion php.

_inspirado en:_

- [codeigniter](https://codeigniter.com/user_guide/libraries/validation.html) - formato y uso de validaciones.
- [laravel](https://laravel.com/docs/8.x/validation) - estilo de las validaciones y funciones.
