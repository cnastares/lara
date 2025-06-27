<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Throwable;

class ImageProcessingService
{
    /**
     * Handle an uploaded image, preserving size and verifying integrity.
     *
     * @param UploadedFile $file
     * @param string $destPath Absolute destination path on disk
     * @return array{path:string,size:int}
     */
    public function handle(UploadedFile $file, string $destPath): array
    {
        $disk = Storage::disk('public');

        $originalSize = $file->getSize();
        $beforeHash = hash_file('sha256', $file->getRealPath());

        try {
            $image = Image::read($file);
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = Str::uuid()->toString().'.'.$extension;

            // Encode image to preserve quality
            $encoded = $image->encode($extension, 90);
            $disk->put($destPath.'/'.$filename, $encoded->toString());
            unset($encoded);

            $storedPath = $disk->path($destPath.'/'.$filename);
            $finalSize = filesize($storedPath);
            $afterHash = hash_file('sha256', $storedPath);

            if (!$afterHash || $afterHash !== $beforeHash) {
                // Integrity failed
                $disk->delete($destPath.'/'.$filename);
                throw new \RuntimeException('Checksum mismatch after processing');
            }

            if ($finalSize > $originalSize * 1.05) {
                Log::warning('Image size increased', [
                    'orig' => $originalSize,
                    'final' => $finalSize,
                ]);
                if ($finalSize > $originalSize * 1.30) {
                    Log::channel('upload')->error('Critical size increase', [
                        'orig' => $originalSize,
                        'final' => $finalSize,
                    ]);
                }
            }

            return ['path' => $destPath.'/'.$filename, 'size' => $finalSize];
        } catch (Throwable $e) {
            Log::channel('upload')->error('Processing failed', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
