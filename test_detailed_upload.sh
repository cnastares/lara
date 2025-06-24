#!/bin/bash
# Script para probar el flujo completo de upload con logs detallados

echo "=== Prueba del flujo de upload con logs detallados ==="

# Limpiar logs anteriores
echo "1. Limpiando logs anteriores..."
> storage/logs/laravel.log

# Limpiar cachés
echo "2. Limpiando cachés..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Limpiar archivos temporales
echo "3. Limpiando archivos temporales..."
rm -rf storage/app/temporary/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*

# Crear directorios necesarios
echo "4. Creando directorios necesarios..."
mkdir -p storage/app/temporary
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views

# Establecer permisos
echo "5. Estableciendo permisos..."
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod -R 777 storage/app/temporary/

# Verificar configuración
echo "6. Verificando configuración..."
echo "upload_max_filesize: $(php -r "echo ini_get('upload_max_filesize');")"
echo "post_max_size: $(php -r "echo ini_get('post_max_size');")"
echo "max_file_uploads: $(php -r "echo ini_get('max_file_uploads');")"
echo "memory_limit: $(php -r "echo ini_get('memory_limit');")"

# Verificar estructura de la base de datos
echo "7. Verificando estructura de la base de datos..."
php artisan tinker --execute="
echo 'Tabla posts columns:';
print_r(Schema::getColumnListing('posts'));
echo 'Image related columns:';
print_r(array_filter(Schema::getColumnListing('posts'), function(\$column) {
    return strpos(strtolower(\$column), 'image') !== false || 
           strpos(strtolower(\$column), 'photo') !== false || 
           strpos(strtolower(\$column), 'picture') !== false;
}));
"

echo ""
echo "=== INSTRUCCIONES DE PRUEBA ==="
echo ""
echo "1. Ve a la página de crear post: http://tu-dominio/posts/create"
echo "2. Sube una imagen usando el componente de upload"
echo "3. Completa el formulario y envía"
echo "4. Verifica que la imagen aparece en el post creado"
echo ""
echo "=== MONITOREO DE LOGS ==="
echo ""
echo "Para monitorear los logs en tiempo real:"
echo "tail -f storage/logs/laravel.log"
echo ""
echo "Los nuevos logs te mostrarán:"
echo "- Estructura de la tabla posts"
echo "- Estado inicial de los datos"
echo "- Procesamiento de cada imagen individual"
echo "- Creación de objetos Upload"
echo "- Llamada al postService->store"
echo "- Resultado del guardado en BD"
echo "- Verificación del post creado"
echo ""
echo "=== LOGS ESPERADOS ==="
echo ""
echo "Deberías ver logs como:"
echo "[timestamp] Database table structure verification"
echo "[timestamp] Starting storeInputDataInDatabase"
echo "[timestamp] Starting image processing in storeInputDataInDatabase"
echo "[timestamp] Processing individual image"
echo "[timestamp] Successfully created Upload object from path"
echo "[timestamp] Completed image processing"
echo "[timestamp] About to call postService->store"
echo "[timestamp] Post service store completed"
echo "[timestamp] Post created successfully"
echo "[timestamp] Post resource retrieved"
echo ""
echo "Si hay algún error, los logs te dirán exactamente dónde falla." 