#!/bin/bash
# Cronos Framework — Compilar Tailwind en modo PRODUCCIÓN
# Genera archivos minificados y optimizados

echo "🚀 Compilando Tailwind CSS v4 para PRODUCCIÓN..."

./tailwindcss -i resources/css/home.css -o public/assets/css/home.css --minify
echo "✓ home.css compilado"

./tailwindcss -i resources/css/error.css -o public/assets/css/error.css --minify
echo "✓ error.css compilado"

echo ""
echo "✅ Build de producción completado. Archivos en public/assets/css/"