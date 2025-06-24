#!/bin/bash

echo "=== PRUEBA COMPLETA DEL FLUJO DE UPLOAD DE IMÁGENES ==="
echo ""

# 1. Limpiar logs y caches
echo "1. Limpiando logs y caches..."
echo "" > storage/logs/laravel.log
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 2. Verificar configuración de storage
echo ""
echo "2. Verificando configuración de storage..."
php artisan tinker --execute="
echo 'Default disk: ' . config('filesystems.default') . PHP_EOL;
echo 'Local disk root: ' . config('filesystems.disks.local.root') . PHP_EOL;
echo 'Public disk root: ' . config('filesystems.disks.public.root') . PHP_EOL;
echo 'Storage app path: ' . storage_path('app') . PHP_EOL;
echo 'Temporary directory: ' . storage_path('app/temporary') . PHP_EOL;
"

# 3. Verificar estructura de tablas
echo ""
echo "3. Verificando estructura de tablas..."
php artisan tinker --execute="
echo 'Posts table columns:' . PHP_EOL;
print_r(Schema::getColumnListing('posts'));
echo 'Pictures table exists: ' . (Schema::hasTable('pictures') ? 'YES' : 'NO') . PHP_EOL;
if (Schema::hasTable('pictures')) {
    echo 'Pictures table columns:' . PHP_EOL;
    print_r(Schema::getColumnListing('pictures'));
}
"

# 4. Crear archivo de prueba
echo ""
echo "4. Creando archivo de prueba..."
TEST_FILE="storage/app/temporary/test-upload-$(date +%s).jpg"
echo "Test image content" > "$TEST_FILE"
echo "Test file created: $TEST_FILE"

# 5. Verificar que el archivo existe
echo ""
echo "5. Verificando que el archivo existe..."
if [ -f "$TEST_FILE" ]; then
    echo "✅ Test file exists: $TEST_FILE"
    echo "File size: $(stat -c%s "$TEST_FILE") bytes"
else
    echo "❌ Test file not found: $TEST_FILE"
fi

# 6. Simular proceso de TmpUpload::image()
echo ""
echo "6. Simulando proceso de TmpUpload::image()..."
php artisan tinker --execute="
use App\Helpers\Common\Files\TmpUpload;
use Illuminate\Http\UploadedFile;

// Crear un archivo de prueba
\$testPath = 'storage/app/temporary/test-upload-' . time() . '.jpg';
file_put_contents(\$testPath, 'Test image content');

// Crear un UploadedFile simulado
\$uploadedFile = new UploadedFile(
    \$testPath,
    'test-image.jpg',
    'image/jpeg',
    null,
    true
);

echo 'Test file created: ' . \$testPath . PHP_EOL;
echo 'File exists before TmpUpload: ' . (file_exists(\$testPath) ? 'YES' : 'NO') . PHP_EOL;

// Simular TmpUpload::image()
\$result = TmpUpload::image(\$uploadedFile, 'temporary/test-uid');

echo 'TmpUpload result: ' . (is_string(\$result) ? \$result : 'ERROR') . PHP_EOL;
echo 'Result file exists: ' . (is_string(\$result) && Storage::disk('local')->exists(\$result) ? 'YES' : 'NO') . PHP_EOL;

if (is_string(\$result)) {
    echo 'File size after TmpUpload: ' . Storage::disk('local')->size(\$result) . ' bytes' . PHP_EOL;
    echo 'Full path: ' . storage_path('app/' . \$result) . PHP_EOL;
    echo 'File exists at full path: ' . (file_exists(storage_path('app/' . \$result)) ? 'YES' : 'NO') . PHP_EOL;
}
"

# 7. Verificar logs
echo ""
echo "7. Verificando logs..."
echo "Últimas 20 líneas del log:"
tail -n 20 storage/logs/laravel.log

echo ""
echo "=== INSTRUCCIONES PARA PRUEBA MANUAL ==="
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