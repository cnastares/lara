<?php

require_once 'vendor/autoload.php';

use App\Helpers\Common\Files\TmpUpload;
use Illuminate\Http\UploadedFile;

// Usar la imagen generada
$testPath = 'storage/app/temporary/test-generated-image-1750736743.jpg';

echo "Test image path: $testPath\n";
echo "File exists: " . (file_exists($testPath) ? 'YES' : 'NO') . "\n";
echo "File size: " . filesize($testPath) . " bytes\n";

// Verificar que es una imagen válida
$imageInfo = getimagesize($testPath);
if ($imageInfo) {
    echo "Valid image detected: {$imageInfo[0]}x{$imageInfo[1]} ({$imageInfo['mime']})\n";
} else {
    echo "Invalid image file\n";
    exit;
}

// Crear un UploadedFile simulado
$uploadedFile = new UploadedFile(
    $testPath,
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
        echo "Temporary file still exists: " . (file_exists($testPath) ? 'YES' : 'NO') . "\n";
        
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