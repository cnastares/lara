#!/bin/bash

echo "=== PRUEBA COMPLETA DEL FLUJO DE UPLOAD DESPUÉS DE CORRECCIONES ==="
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
echo 'Local disk throw: ' . (config('filesystems.disks.local.throw') ? 'YES' : 'NO') . PHP_EOL;
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

# 4. Crear imagen de prueba
echo ""
echo "4. Creando imagen de prueba..."
php create_test_image.php

# 5. Verificar función hasTemporaryPath
echo ""
echo "5. Verificando función hasTemporaryPath..."
php artisan tinker --execute="
echo 'Testing hasTemporaryPath function:' . PHP_EOL;
echo 'temporary/image.jpg: ' . (hasTemporaryPath('temporary/image.jpg') ? 'YES' : 'NO') . PHP_EOL;
echo 'temporary/abc123/image.jpg: ' . (hasTemporaryPath('temporary/abc123/image.jpg') ? 'YES' : 'NO') . PHP_EOL;
echo 'temporary\\\\abc123\\\\image.jpg: ' . (hasTemporaryPath('temporary\\\\abc123\\\\image.jpg') ? 'YES' : 'NO') . PHP_EOL;
echo 'public/temporary/image.jpg: ' . (hasTemporaryPath('public/temporary/image.jpg') ? 'YES' : 'NO') . PHP_EOL;
echo 'other/path/image.jpg: ' . (hasTemporaryPath('other/path/image.jpg') ? 'YES' : 'NO') . PHP_EOL;
"

# 6. Verificar archivos temporales existentes
echo ""
echo "6. Verificando archivos temporales existentes..."
php artisan tinker --execute="
echo 'Temporary files in storage:' . PHP_EOL;
\$files = Storage::disk('local')->allFiles('temporary');
if (empty(\$files)) {
    echo 'No temporary files found' . PHP_EOL;
} else {
    foreach (\$files as \$file) {
        echo '- ' . \$file . ' (size: ' . Storage::disk('local')->size(\$file) . ' bytes)' . PHP_EOL;
    }
}
"

# 7. Simular proceso completo
echo ""
echo "7. Simulando proceso completo de upload y procesamiento..."
php artisan tinker --execute="
use App\Helpers\Common\Files\TmpUpload;
use Illuminate\Http\UploadedFile;

// Usar la imagen generada
\$testPath = 'storage/app/temporary/test-generated-image-' . time() . '.jpg';

// Crear imagen de prueba si no existe
if (!file_exists(\$testPath)) {
    \$image = imagecreate(100, 100);
    \$red = imagecolorallocate(\$image, 255, 0, 0);
    imagefilledrectangle(\$image, 10, 10, 90, 90, \$red);
    imagejpeg(\$image, \$testPath, 90);
    imagedestroy(\$image);
}

echo 'Test image path: ' . \$testPath . PHP_EOL;
echo 'File exists: ' . (file_exists(\$testPath) ? 'YES' : 'NO') . PHP_EOL;

// Crear un UploadedFile simulado
\$uploadedFile = new UploadedFile(
    \$testPath,
    'test-image.jpg',
    'image/jpeg',
    null,
    true
);

echo 'UploadedFile created successfully' . PHP_EOL;

// Simular TmpUpload::image()
try {
    \$result = TmpUpload::image(\$uploadedFile, 'temporary/test-uid');
    
    echo 'TmpUpload result: ' . (is_string(\$result) ? \$result : 'ERROR') . PHP_EOL;
    
    if (is_string(\$result)) {
        echo 'Result file exists: ' . (Storage::disk('local')->exists(\$result) ? 'YES' : 'NO') . PHP_EOL;
        echo 'File size after TmpUpload: ' . Storage::disk('local')->size(\$result) . ' bytes' . PHP_EOL;
        
        // Simular el proceso de guardado en BD
        echo 'Simulating database save process...' . PHP_EOL;
        
        // Verificar que el archivo temporal sigue existiendo después de un tiempo
        sleep(2);
        echo 'After 2 seconds - Temporary file exists: ' . (Storage::disk('local')->exists(\$result) ? 'YES' : 'NO') . PHP_EOL;
        
        // Simular hasTemporaryPath
        echo 'hasTemporaryPath result: ' . (hasTemporaryPath(\$result) ? 'YES' : 'NO') . PHP_EOL;
        
    } else {
        echo 'TmpUpload failed with result type: ' . gettype(\$result) . PHP_EOL;
    }
} catch (Exception \$e) {
    echo 'Error in TmpUpload: ' . \$e->getMessage() . PHP_EOL;
    echo 'Error class: ' . get_class(\$e) . PHP_EOL;
}
"

# 8. Verificar logs
echo ""
echo "8. Verificando logs..."
echo "Últimas 20 líneas del log:"
tail -n 20 storage/logs/laravel.log

echo ""
echo "=== RESUMEN DE CORRECCIONES IMPLEMENTADAS ==="
echo ""
echo "✅ 1. Configuración de storage corregida: default disk = 'local'"
echo "✅ 2. Función hasTemporaryPath mejorada para rutas anidadas"
echo "✅ 3. Limpieza automática de archivos temporales DESACTIVADA"
echo "✅ 4. Lógica de recuperación de archivos implementada"
echo "✅ 5. Logs detallados agregados en cada etapa"
echo "✅ 6. Uso consistente de Storage::disk('local')"
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
echo "Los logs te mostrarán:"
echo "- Configuración de storage"
echo "- Proceso de upload y guardado"
echo "- Verificación de existencia de archivos"
echo "- Intentos de recuperación si es necesario"
echo "- Resultado final del procesamiento"
echo ""
echo "=== RESULTADO ESPERADO ==="
echo ""
echo "✅ Las imágenes se suben correctamente a storage/app/temporary/"
echo "✅ Los archivos temporales persisten hasta el guardado del post"
echo "✅ El sistema recupera archivos si se pierden"
echo "✅ El post se crea con imágenes adjuntas"
echo "✅ Los logs muestran todo el flujo de manera clara" 