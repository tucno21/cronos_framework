@echo off
REM Cronos Framework - Compilar Tailwind en modo PRODUCCION
REM Genera archivos minificados y optimizados

echo.
echo ========================================
echo   Compilando Tailwind CSS v4 para PRODUCCION...
echo ========================================
echo.

REM Compilar home.css para produccion
tailwindcss -i resources/css/home.css -o public/assets/css/home.css --minify
if %errorlevel% neq 0 (
    echo [ERROR] Error compilando home.css
    exit /b %errorlevel%
)
echo [OK] home.css compilado

REM Compilar dashboard.css para produccion
tailwindcss -i resources/css/dashboard.css -o public/assets/css/dashboard.css --minify
if %errorlevel% neq 0 (
    echo [ERROR] Error compilando dashboard.css
    exit /b %errorlevel%
)
echo [OK] dashboard.css compilado

REM Compilar error.css para produccion
tailwindcss -i resources/css/error.css -o public/assets/css/error.css --minify
if %errorlevel% neq 0 (
    echo [ERROR] Error compilando error.css
    exit /b %errorlevel%
)
echo [OK] error.css compilado

echo.
echo ========================================
echo   Build de produccion completado
echo   Archivos generados en: public/assets/css/
echo ========================================