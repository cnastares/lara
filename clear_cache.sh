#!/bin/bash
# Script para limpiar cachés de Laravel y resolver problemas de archivos temporales

echo "Limpiando cachés de Laravel..."

# Limpiar cachés
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Limpiar archivos temporales de uploads
echo "Limpiando archivos temporales..."
rm -rf storage/app/temporary/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*

# Verificar permisos de directorios
echo "Verificando permisos..."
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

echo "Limpieza completada. Intenta subir archivos nuevamente." 