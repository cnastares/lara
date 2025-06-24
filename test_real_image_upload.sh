#!/bin/bash

echo "=== PRUEBA CON IMAGEN REAL ==="
echo ""

# 1. Limpiar logs
echo "1. Limpiando logs..."
echo "" > storage/logs/laravel.log

# 2. Crear imagen de prueba real (1x1 pixel PNG)
echo "2. Creando imagen de prueba real..."
TEST_IMAGE="storage/app/temporary/test-image-$(date +%s).png"

# Crear un PNG válido de 1x1 pixel
cat > "$TEST_IMAGE" << 'EOF'
PNG

   IHDR           sRGB    gAMA  a   	pHYs  .  .  od   IDATc`   IENDB`
EOF

echo "Test image created: $TEST_IMAGE"

# 3. Verificar que la imagen existe
echo ""
echo "3. Verificando que la imagen existe..."
if [ -f "$TEST_IMAGE" ]; then
    echo "✅ Test image exists: $TEST_IMAGE"
    echo "File size: $(stat -c%s "$TEST_IMAGE") bytes"
else
    echo "❌ Test image not found: $TEST_IMAGE"
fi

# 4. Simular upload con imagen real
echo ""
echo "4. Simulando upload con imagen real..."
php artisan tinker --execute="
use App\Helpers\Common\Files\TmpUpload;
use Illuminate\Http\UploadedFile;

// Usar la imagen real creada
\$testPath = '$TEST_IMAGE';

echo 'Test image path: ' . \$testPath . PHP_EOL;
echo 'File exists: ' . (file_exists(\$testPath) ? 'YES' : 'NO') . PHP_EOL;
echo 'File size: ' . filesize(\$testPath) . ' bytes' . PHP_EOL;

// Crear un UploadedFile simulado
\$uploadedFile = new UploadedFile(
    \$testPath,
    'test-image.png',
    'image/png',
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
    }
} catch (Exception \$e) {
    echo 'Error in TmpUpload: ' . \$e->getMessage() . PHP_EOL;
}
"

# 5. Verificar logs
echo ""
echo "5. Verificando logs..."
echo "Últimas 10 líneas del log:"
tail -n 10 storage/logs/laravel.log

echo ""
echo "=== PRÓXIMOS PASOS ==="
echo ""
echo "Si el upload funciona, prueba el flujo completo:"
echo "1. Ve a http://tu-dominio/posts/create"
echo "2. Sube una imagen real"
echo "3. Completa el formulario"
echo "4. Verifica que la imagen aparece en el post"
echo ""
echo "Monitorea los logs: tail -f storage/logs/laravel.log" 