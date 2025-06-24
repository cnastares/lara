#!/bin/bash
# Script para probar la persistencia de archivos temporales

echo "=== Prueba de Persistencia de Archivos Temporales ==="

# Limpiar logs anteriores
echo "1. Limpiando logs anteriores..."
> storage/logs/laravel.log

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

# Verificar configuración de storage
echo "5. Verificando configuración de storage..."
echo "Default disk: $(php artisan tinker --execute="echo config('filesystems.default');")"
echo "Local disk root: $(php artisan tinker --execute="echo config('filesystems.disks.local.root');")"
echo "Storage app path: $(php artisan tinker --execute="echo storage_path('app');")"
echo "Temporary directory: $(php artisan tinker --execute="echo storage_path('app/temporary');")"

# Verificar estructura de base de datos
echo "6. Verificando estructura de base de datos..."
php artisan tinker --execute="
echo 'Posts table columns:';
print_r(Schema::getColumnListing('posts'));

echo 'Image related columns:';
print_r(array_filter(Schema::getColumnListing('posts'), function(\$column) {
    return strpos(strtolower(\$column), 'image') !== false || 
           strpos(strtolower(\$column), 'photo') !== false || 
           strpos(strtolower(\$column), 'picture') !== false;
}));

echo 'Checking related tables:';
echo 'Pictures table exists: ' . (Schema::hasTable('pictures') ? 'YES' : 'NO') . PHP_EOL;
echo 'Post pictures table exists: ' . (Schema::hasTable('post_pictures') ? 'YES' : 'NO') . PHP_EOL;
echo 'Uploads table exists: ' . (Schema::hasTable('uploads') ? 'YES' : 'NO') . PHP_EOL;
echo 'Files table exists: ' . (Schema::hasTable('files') ? 'YES' : 'NO') . PHP_EOL;

if (Schema::hasTable('pictures')) {
    echo 'Pictures table columns:';
    print_r(Schema::getColumnListing('pictures'));
}
"

# Crear archivo de prueba temporal
echo "7. Creando archivo de prueba temporal..."
TEST_FILE="storage/app/temporary/test-file-$(date +%s).txt"
echo "Test content $(date)" > "$TEST_FILE"
echo "Test file created: $TEST_FILE"

# Verificar que el archivo existe
echo "8. Verificando que el archivo existe..."
if [ -f "$TEST_FILE" ]; then
    echo "✅ Test file exists: $TEST_FILE"
    echo "File size: $(stat -c%s "$TEST_FILE") bytes"
else
    echo "❌ Test file does not exist: $TEST_FILE"
fi

# Simular el proceso de upload
echo "9. Simulando proceso de upload..."
php artisan tinker --execute="
// Simular el proceso de TmpUpload::image()
\$testPath = 'temporary/test-file-" . $(date +%s) . ".txt';
\$content = 'Test content from upload simulation';

// Guardar archivo
Storage::disk('local')->put(\$testPath, \$content);

echo 'File saved to: ' . \$testPath . PHP_EOL;
echo 'File exists: ' . (Storage::disk('local')->exists(\$testPath) ? 'YES' : 'NO') . PHP_EOL;
echo 'File size: ' . (Storage::disk('local')->exists(\$testPath) ? Storage::disk('local')->size(\$testPath) : 'NOT_FOUND') . PHP_EOL;

// Verificar directorio temporal
echo 'Temporary directory contents:';
print_r(Storage::disk('local')->files('temporary'));
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
echo "- Configuración de storage"
echo "- Estructura de tablas relacionadas con imágenes"
echo "- Verificación de guardado de archivos temporales"
echo "- Análisis de timing del proceso"
echo "- Intentos de recuperación de archivos"
echo "- Resultado final del procesamiento"
echo ""
echo "=== LOGS ESPERADOS ==="
echo ""
echo "Deberías ver logs como:"
echo "[timestamp] Storage configuration verification"
echo "[timestamp] Checking related image tables"
echo "[timestamp] Before saving temporary file"
echo "[timestamp] After saving temporary file"
echo "[timestamp] Timing analysis"
echo "[timestamp] Temporary file not found, attempting recovery"
echo "[timestamp] Recovery attempt results"
echo "[timestamp] Using recovered file"
echo ""
echo "Si el archivo temporal se pierde, el sistema intentará recuperarlo." 