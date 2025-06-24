#!/bin/bash

echo "=== PRUEBA CON IMAGEN REAL DE INTERNET ==="
echo ""

# 1. Limpiar logs
echo "1. Limpiando logs..."
echo "" > storage/logs/laravel.log

# 2. Descargar imagen real de internet
echo "2. Descargando imagen real..."
TEST_IMAGE="storage/app/temporary/test-real-image-$(date +%s).jpg"

# Descargar una imagen de prueba de 1x1 pixel
curl -s "https://via.placeholder.com/100x100.jpg" -o "$TEST_IMAGE"

if [ -f "$TEST_IMAGE" ]; then
    echo "✅ Imagen descargada: $TEST_IMAGE"
    echo "File size: $(stat -c%s "$TEST_IMAGE") bytes"
else
    echo "❌ No se pudo descargar la imagen"
    exit 1
fi

# 3. Verificar que es una imagen válida
echo ""
echo "3. Verificando que es una imagen válida..."
file "$TEST_IMAGE"

# 4. Simular upload con imagen real
echo ""
echo "4. Simulando upload con imagen real..."
php artisan tinker --execute="
use App\Helpers\Common\Files\TmpUpload;
use Illuminate\Http\UploadedFile;

// Usar la imagen real descargada
\$testPath = '$TEST_IMAGE';

echo 'Test image path: ' . \$testPath . PHP_EOL;
echo 'File exists: ' . (file_exists(\$testPath) ? 'YES' : 'NO') . PHP_EOL;
echo 'File size: ' . filesize(\$testPath) . ' bytes' . PHP_EOL;

// Verificar que es una imagen válida
\$imageInfo = getimagesize(\$testPath);
if (\$imageInfo) {
    echo 'Valid image detected: ' . \$imageInfo[0] . 'x' . \$imageInfo[1] . ' (' . \$imageInfo['mime'] . ')' . PHP_EOL;
} else {
    echo 'Invalid image file' . PHP_EOL;
    exit;
}

// Crear un UploadedFile simulado
\$uploadedFile = new UploadedFile(
    \$testPath,
    'test-real-image.jpg',
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
        echo 'Full path: ' . storage_path('app/' . \$result) . PHP_EOL;
        echo 'File exists at full path: ' . (file_exists(storage_path('app/' . \$result)) ? 'YES' : 'NO') . PHP_EOL;
        
        // Verificar que el archivo temporal persiste
        echo 'Temporary file still exists: ' . (file_exists(\$testPath) ? 'YES' : 'NO') . PHP_EOL;
        
        // Simular el proceso de guardado en BD
        echo 'Simulating database save process...' . PHP_EOL;
        
        // Verificar que el archivo temporal sigue existiendo después de un tiempo
        sleep(2);
        echo 'After 2 seconds - Temporary file exists: ' . (Storage::disk('local')->exists(\$result) ? 'YES' : 'NO') . PHP_EOL;
        
    } else {
        echo 'TmpUpload failed with result type: ' . gettype(\$result) . PHP_EOL;
    }
} catch (Exception \$e) {
    echo 'Error in TmpUpload: ' . \$e->getMessage() . PHP_EOL;
    echo 'Error class: ' . get_class(\$e) . PHP_EOL;
}
"

# 5. Verificar logs
echo ""
echo "5. Verificando logs..."
echo "Últimas 15 líneas del log:"
tail -n 15 storage/logs/laravel.log

echo ""
echo "=== CONCLUSIÓN ==="
echo ""
echo "Si el upload funciona con una imagen real, entonces:"
echo "✅ El problema NO está en la pérdida de archivos temporales"
echo "✅ El problema está en el procesamiento de imágenes no válidas"
echo "✅ El flujo AJAX -> Formulario funciona correctamente"
echo ""
echo "=== PRÓXIMOS PASOS ==="
echo ""
echo "1. Prueba el flujo completo con una imagen real:"
echo "   - Ve a http://tu-dominio/posts/create"
echo "   - Sube una imagen real (JPG, PNG)"
echo "   - Completa el formulario"
echo "   - Verifica que la imagen aparece en el post"
echo ""
echo "2. Si funciona, el problema está resuelto"
echo "3. Si no funciona, revisa los logs para ver el error específico" 