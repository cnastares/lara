#!/bin/bash
# Script para diagnosticar y resolver problemas de upload de archivos

echo "=== Diagnóstico de problemas de upload ==="

# Verificar configuración PHP
echo "1. Verificando configuración PHP..."
echo "upload_max_filesize: $(php -r "echo ini_get('upload_max_filesize');")"
echo "post_max_size: $(php -r "echo ini_get('post_max_size');")"
echo "max_file_uploads: $(php -r "echo ini_get('max_file_uploads');")"
echo "upload_tmp_dir: $(php -r "echo ini_get('upload_tmp_dir');")"
echo "memory_limit: $(php -r "echo ini_get('memory_limit');")"
echo "tmp_dir: $(php -r "echo sys_get_temp_dir();")"

# Verificar permisos de directorios
echo ""
echo "2. Verificando permisos de directorios..."
echo "storage/ permissions: $(ls -ld storage/)"
echo "bootstrap/cache/ permissions: $(ls -ld bootstrap/cache/)"
echo "storage/app/temporary/ permissions: $(ls -ld storage/app/temporary/ 2>/dev/null || echo 'Directory does not exist')"

# Limpiar cachés de Laravel
echo ""
echo "3. Limpiando cachés de Laravel..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Limpiar archivos temporales
echo ""
echo "4. Limpiando archivos temporales..."
rm -rf storage/app/temporary/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*

# Crear directorios si no existen
echo ""
echo "5. Creando directorios necesarios..."
mkdir -p storage/app/temporary
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views

# Establecer permisos correctos
echo ""
echo "6. Estableciendo permisos correctos..."
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod -R 777 storage/app/temporary/

# Verificar que los directorios son escribibles
echo ""
echo "7. Verificando que los directorios son escribibles..."
if [ -w storage/app/temporary ]; then
    echo "✓ storage/app/temporary es escribible"
else
    echo "✗ storage/app/temporary NO es escribible"
fi

if [ -w storage/framework/cache ]; then
    echo "✓ storage/framework/cache es escribible"
else
    echo "✗ storage/framework/cache NO es escribible"
fi

echo ""
echo "=== Diagnóstico completado ==="
echo "Intenta subir archivos nuevamente. Si el problema persiste, revisa los logs en storage/logs/laravel.log" 