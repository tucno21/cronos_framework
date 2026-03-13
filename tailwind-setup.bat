@echo off
REM Descargar Tailwind CLI v4 standalone para Windows (sin Node.js)

echo Descargando Tailwind CLI v4 para Windows-x64...
curl -sLO "https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-windows-x64.exe"
move tailwindcss-windows-x64.exe tailwindcss.exe

echo.
echo ✓ Tailwind CLI v4 instalado:
tailwindcss --version