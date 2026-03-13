#!/bin/bash
# Descargar Tailwind CLI v4 standalone (sin Node.js)
# Detectar OS y arquitectura automáticamente

OS=$(uname -s | tr '[:upper:]' '[:lower:]')
ARCH=$(uname -m)

if [ "$ARCH" = "x86_64" ]; then ARCH="x64"; fi
if [ "$ARCH" = "aarch64" ]; then ARCH="arm64"; fi

echo "Descargando Tailwind CLI v4 para $OS-$ARCH..."

curl -sLO "https://github.com/tailwindlabs/tailwindcss/releases/latest/download/tailwindcss-$OS-$ARCH"
mv "tailwindcss-$OS-$ARCH" tailwindcss
chmod +x tailwindcss

echo "✓ Tailwind CLI v4 instalado: $(./tailwindcss --version)"