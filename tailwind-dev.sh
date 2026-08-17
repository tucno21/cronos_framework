#!/bin/bash
# Cronos Framework — Compilar Tailwind en modo DESARROLLO
# Ejecuta todos los watchers en paralelo

echo "🔧 Iniciando Tailwind CSS v4 en modo DESARROLLO..."
echo "   Presiona Ctrl+C para detener todos los procesos"
echo ""

./tailwindcss -i resources/css/home.css -o public/assets/css/home.css --watch &
./tailwindcss -i resources/css/error.css -o public/assets/css/error.css --watch &

echo "✓ Watchers activos para: home.css, error.css"
echo "  Ubicación: public/assets/css/"
wait