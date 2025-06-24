<?php

// Crear una imagen de prueba válida
$width = 100;
$height = 100;

// Crear una imagen
$image = imagecreate($width, $height);

// Definir colores
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);
$red = imagecolorallocate($image, 255, 0, 0);

// Dibujar un rectángulo rojo
imagefilledrectangle($image, 10, 10, 90, 90, $red);

// Dibujar texto
imagestring($image, 5, 20, 40, 'TEST', $white);

// Guardar la imagen
$testImagePath = 'storage/app/temporary/test-generated-image-' . time() . '.jpg';
imagejpeg($image, $testImagePath, 90);

// Liberar memoria
imagedestroy($image);

echo "Test image created: $testImagePath\n";
echo "File size: " . filesize($testImagePath) . " bytes\n";
echo "File exists: " . (file_exists($testImagePath) ? 'YES' : 'NO') . "\n";

// Verificar que es una imagen válida
$imageInfo = getimagesize($testImagePath);
if ($imageInfo) {
    echo "Valid image detected: {$imageInfo[0]}x{$imageInfo[1]} ({$imageInfo['mime']})\n";
} else {
    echo "Invalid image file\n";
}

echo "\n=== TESTING TMPUPLOAD ===\n";

// Simular TmpUpload::image()
require_once 'vendor/autoload.php';

use App\Helpers\Common\Files\TmpUpload;
use Illuminate\Http\UploadedFile;

// Crear un UploadedFile simulado
$uploadedFile = new UploadedFile(
    $testImagePath,
    'test-generated-image.jpg',
    'image/jpeg',
    null,
    true
);

echo "UploadedFile created successfully\n";

// Simular TmpUpload::image()
try {
    $result = TmpUpload::image($uploadedFile, 'temporary/test-uid');
    
    echo "TmpUpload result: " . (is_string($result) ? $result : 'ERROR') . "\n";
    
    if (is_string($result)) {
        echo "Result file exists: " . (Storage::disk('local')->exists($result) ? 'YES' : 'NO') . "\n";
        echo "File size after TmpUpload: " . Storage::disk('local')->size($result) . " bytes\n";
        echo "Full path: " . storage_path('app/' . $result) . "\n";
        echo "File exists at full path: " . (file_exists(storage_path('app/' . $result)) ? 'YES' : 'NO') . "\n";
        
        // Verificar que el archivo temporal persiste
        echo "Temporary file still exists: " . (file_exists($testImagePath) ? 'YES' : 'NO') . "\n";
        
        // Simular el proceso de guardado en BD
        echo "Simulating database save process...\n";
        
        // Verificar que el archivo temporal sigue existiendo después de un tiempo
        sleep(2);
        echo "After 2 seconds - Temporary file exists: " . (Storage::disk('local')->exists($result) ? 'YES' : 'NO') . "\n";
        
    } else {
        echo "TmpUpload failed with result type: " . gettype($result) . "\n";
    }
} catch (Exception $e) {
    echo "Error in TmpUpload: " . $e->getMessage() . "\n";
    echo "Error class: " . get_class($e) . "\n";
}

echo "\n=== CONCLUSION ===\n";
echo "If the upload works with a real image, then:\n";
echo "✅ The problem is NOT in temporary file loss\n";
echo "✅ The problem is in processing invalid images\n";
echo "✅ The AJAX -> Form flow works correctly\n"; 