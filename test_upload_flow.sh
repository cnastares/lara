#!/bin/bash
# Script para probar el flujo completo de upload de imágenes

echo "=== Prueba del flujo de upload de imágenes ==="

# Limpiar cachés
echo "1. Limpiando cachés..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Limpiar archivos temporales
echo "2. Limpiando archivos temporales..."
rm -rf storage/app/temporary/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*

# Crear directorios necesarios
echo "3. Creando directorios necesarios..."
mkdir -p storage/app/temporary
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views

# Establecer permisos
echo "4. Estableciendo permisos..."
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod -R 777 storage/app/temporary/

# Verificar configuración
echo "5. Verificando configuración..."
echo "upload_max_filesize: $(php -r "echo ini_get('upload_max_filesize');")"
echo "post_max_size: $(php -r "echo ini_get('post_max_size');")"
echo "max_file_uploads: $(php -r "echo ini_get('max_file_uploads');")"
echo "memory_limit: $(php -r "echo ini_get('memory_limit');")"

# Verificar rutas
echo "6. Verificando rutas..."
php artisan route:list | grep -E "(photos|upload)" | head -10

echo ""
echo "=== Instrucciones de prueba ==="
echo "1. Ve a la página de crear post: http://tu-dominio/posts/create"
echo "2. Sube una imagen usando el componente de upload"
echo "3. Completa el formulario y envía"
echo "4. Verifica que la imagen aparece en el post creado"
echo ""
echo "Para monitorear los logs en tiempo real:"
echo "tail -f storage/logs/laravel.log"
echo ""
echo "Si hay problemas, revisa los logs para ver el diagnóstico detallado." 