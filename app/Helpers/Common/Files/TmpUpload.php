<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Helpers\Common\Files;

use App\Helpers\Common\Files\Storage\StorageDisk;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\Laravel\Facades\Image;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Throwable;

class TmpUpload
{
	/**
	 * @param $file
	 * @param string $tmpUploadDir
     * @param string|null $filename
     * @return string|JsonResponse|null
     */
    public static function image($file, string $tmpUploadDir, ?string $filename = null): string|JsonResponse|null
	{
        if (!$file instanceof SymfonyUploadedFile) {
                return null;
        }

        // Verificar si el archivo es válido primero
        if (!$file->isValid()) {
                Log::warning('Invalid uploaded file', [
                        'file'       => $file->getClientOriginalName(),
                        'error_code' => $file->getError(),
                        'error_message' => $file->getErrorMessage(),
                ]);
                return response()->json(['error' => 'Archivo inválido: ' . $file->getErrorMessage()], 422);
        }

        // En Windows, getRealPath() puede fallar con archivos temporales, pero getPathname() funciona
        $path = $file->getRealPath();
        if (empty($path) || !file_exists($path)) {
                $path = $file->getPathname();
                
                // Verificar si el archivo temporal existe usando getPathname()
                if (empty($path) || !file_exists($path)) {
                        Log::warning('Temporary file missing', [
                                'file'       => $file->getClientOriginalName(),
                                'path'       => (string)$path,
                                'pathname'   => $file->getPathname(),
                                'realpath'   => $file->getRealPath(),
                                'livewire'   => request()->header('X-Livewire') ? true : false,
                                'files_count'=> is_countable(request()->file('pictures')) ? count(request()->file('pictures')) : 0,
                        ]);

                        return response()->json(['error' => 'El archivo temporal no se encuentra disponible. Intente subir el archivo nuevamente.'], 422);
                }
        }

        $disk = StorageDisk::getDisk();
		
		try {
			// Get file's original infos
			$origFilename = $file->getClientOriginalName();
			$origExtension = $file->getClientOriginalExtension();
			$origExtension = !empty($origExtension) ? $origExtension : getClientImageFallbackExtension();
			
			// Get the client extension for the original extension
			$extension = getClientImageExtensionFor($origExtension);
			
			// Image quality
			$imageQuality = (int)config('settings.upload.image_quality', 90);
			
			// Image trimming tolerance
			$trimmingTolerance = (int)config('settings.upload.image_trimming_tolerance', 0);
			
			// Image progressively rending
			$isProgressive = (config('settings.upload.image_progressive') == '1');
			
			// Image default dimensions
			$width = (int)config('settings.upload.img_resize_width', 1000);
			$height = (int)config('settings.upload.img_resize_height', 1000);
			
			// Other parameters
			$ratio = config('settings.upload.img_resize_ratio', '1');
			$upsize = config('settings.upload.img_resize_upsize', '0');
			
			// Verificar que el archivo es legible antes de procesarlo
			if (!is_readable($path)) {
				Log::error('File not readable', [
					'file' => $file->getClientOriginalName(),
					'path' => $path,
				]);
				return response()->json(['error' => 'No se puede leer el archivo.'], 422);
			}
			
			// Init. Intervention - usar el path directamente en lugar del objeto UploadedFile
			$image = Image::read($path);
			
			// Orient image according to exif data
			if (isExifExtensionEnabled()) {
				$image = $image->orient();
			}
			
			// Image trimming
			if ($trimmingTolerance > 0) {
				$image = $image->trim($trimmingTolerance);
			}
			
			// If the original dimensions are higher than the resize dimensions
			// OR the 'upsize' option is enable, then resize the image
			if ($image->width() > $width || $image->height() > $height || $upsize == '1') {
				// Resize
				if ($ratio != '1' && $upsize != '1') {
					$image = $image->resizeDown($width, $height);
				} else {
					if ($ratio == '1' && $upsize == '1') {
						$image = $image->scale($width, $height);
					} else {
						$image = ($ratio == '1')
							? $image->scaleDown($width, $height)
							: $image->resize($width, $height);
					}
				}
			}
			
			// Encode the Image!
			$encodedImage = $image->encodeByExtension($extension, progressive: $isProgressive, quality: $imageQuality);
			unset($image);
			
			// Generate the filename
			$filename = normalizeFilename($origFilename, $filename);
			$filename = $filename . '.' . $extension;
			
			// Get the file path
			$filePath = $tmpUploadDir . '/' . $filename;
			
			// Store the image on disk
			$disk->put($filePath, $encodedImage->toString());
			unset($encodedImage);
			
			// Return the path (to the database later)
			return $filePath;
        } catch (NotReadableException|ValueError|Throwable $e) {
                $inputExcerpt = null;
                try {
                        $contents = File::get($path);
                        if (is_string($contents) && str_starts_with($contents, 'data:')) {
                                $inputExcerpt = substr($contents, 0, 100);
                        }
                } catch (Throwable $e2) {
                }

                Log::error('Image upload error', [
                        'message'        => $e->getMessage(),
                        'file'           => $file->getClientOriginalName() ?? null,
                        'path'           => $path,
                        'input_excerpt'  => $inputExcerpt,
                        'exception_class'=> get_class($e),
                ]);

                return response()->json(['error' => 'Imagen no válida o corrupta. Intente con otra imagen.'], 422);
        }
	}
	
	/**
	 * @param $file
	 * @param string $tmpUploadDir
	 * @param string|null $filename
	 * @return string|null
	 */
	public static function file($file, string $tmpUploadDir, ?string $filename = null): ?string
	{
		if (!$file instanceof SymfonyUploadedFile) {
			return null;
		}
		
		$disk = StorageDisk::getDisk();
		
		try {
			// Get file original infos
			$origFilename = $file->getClientOriginalName();
			$origExtension = $file->getClientOriginalExtension();
			
			// Generate a filename
			$filename = normalizeFilename($origFilename, $filename);
			$filename = $filename . '.' . $origExtension;
			
			// Get filepath
			$filePath = $tmpUploadDir . '/' . $filename;
			
			// Store the file on disk
			$disk->put($filePath, File::get($file->getrealpath()));
			
			// Return the path (to the database later)
			return $filePath;
		} catch (Throwable $e) {
		}
		
		return null;
	}
}
