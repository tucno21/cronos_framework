@echo off
REM Cronos Framework - Compilar Tailwind en modo DESARROLLO
REM Ejecuta cada watcher en ventana separada

echo.
echo ========================================
echo   Iniciando Tailwind CSS v4 en modo DESARROLLO...
echo   Se abriran ventanas separadas para cada watcher
echo   Cierra las ventanas para detener los procesos
echo ========================================
echo.

REM Compilar home.css en watch (ventana separada)
start "Tailwind - Home" cmd /k "tailwindcss -i resources/css/home.css -o public/assets/css/home.css --watch"

REM Compilar error.css en watch (ventana separada)
start "Tailwind - Error" cmd /k "tailwindcss -i resources/css/error.css -o public/assets/css/error.css --watch"

echo.
echo [OK] Watchers activos para: home.css, error.css
echo      Ubicacion: public/assets/css/