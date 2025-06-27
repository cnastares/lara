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

namespace App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Create;

use App\Helpers\Common\Files\TmpUpload;
use App\Http\Requests\Front\PhotoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Common\Files\Storage\StorageDisk;
use Illuminate\Support\Str;
use Throwable;

class PhotoController extends BaseController
{
	/**
	 * Listing pictures' step
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function showForm()
	{
		// Return to the last unlocked step if the current step remains locked
		$currentStep = $this->getStepByKey(get_class($this));
		$lastUnlockedStepUrl = $this->getLastUnlockedStepUrlOnlyIfGivenStepIsLocked($currentStep);
		if (!empty($lastUnlockedStepUrl)) {
			return redirect()->to($lastUnlockedStepUrl)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		// Check if the 'Pricing Page' must be started first, and make redirection to it.
		$pricingUrl = $this->getPricingPage($this->getSelectedPackage());
		if (!empty($pricingUrl)) {
			return redirect()->to($pricingUrl)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		// Create an unique temporary ID
		if (!session()->has('uid')) {
			session()->put('uid', generateUniqueCode(9));
		}
		
		$picturesInput = session('picturesInput');
		
		// Get steps URLs & labels
		$previousStepUrl = $this->getPrevStepUrl($currentStep);
		$previousStepLabel = '<i class="bi bi-chevron-left"></i>  ' . t('Previous');
		$formActionUrl = request()->fullUrl();
		if (
			isset($this->countPackages, $this->countPaymentMethods)
			&& $this->countPackages > 0
			&& $this->countPaymentMethods > 0
			&& doesNoPackageOrPremiumOneSelected()
		) {
			$nextStepUrl = $this->getNextStepUrl($currentStep);
			$nextStepLabel = t('Next') . '  <i class="bi bi-chevron-right"></i>';
		} else {
			$nextStepUrl = $this->getNextStepUrl($currentStep + 1);
			$nextStepLabel = t('submit');
		}
		
		// Share steps URLs & label variables
		view()->share('previousStepUrl', $previousStepUrl);
		view()->share('previousStepLabel', $previousStepLabel);
		view()->share('formActionUrl', $formActionUrl);
		view()->share('nextStepUrl', $nextStepUrl);
		view()->share('nextStepLabel', $nextStepLabel);
		
		return view('front.post.createOrEdit.multiSteps.create.photos', compact('picturesInput'));
	}
	
	/**
	 * Listing pictures' step (POST)
	 *
	 * @param \App\Http\Requests\Front\PhotoRequest $request
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function postForm(PhotoRequest $request): JsonResponse|RedirectResponse
	{
		$requestId = $request->header('X-Request-Id') ?? Str::uuid()->toString();
		if (!isFromAjax($request)) {
			// Return to the last unlocked step if the current step remains locked
			$currentStep = $this->getStepByKey(get_class($this));
			$lastUnlockedStepUrl = $this->getLastUnlockedStepUrlOnlyIfGivenStepIsLocked($currentStep);
			if (!empty($lastUnlockedStepUrl)) {
				return redirect()->to($lastUnlockedStepUrl)->withHeaders(config('larapen.core.noCacheHeaders'));
			}
		}
		
		$savedPicturesInput = (array)session('picturesInput');
		
		// Get default/global pictures limit
		$defaultPicturesLimit = (int)config('settings.listing_form.pictures_limit', 5);
		
		// Get the picture number limit
		$countExistingPictures = count($savedPicturesInput);
		$picturesLimit = $defaultPicturesLimit - $countExistingPictures;
		
		// Use unique ID to store post's pictures
		if (session()->has('uid')) {
			$this->tmpUploadDir = $this->tmpUploadDir . '/' . session('uid');
		}
		
                $picturesInput = [];

                // Save uploaded files
                $files = $request->file('pictures');

                if (isFromAjax($request)) {
                        Log::debug('Upload request received', [
                                'files_count'  => count($request->allFiles()),
                                'has_pictures' => $request->hasFile('pictures'),
                                'request_data' => $request->except(['_token'])
                        ]);

                        if (!$request->hasFile('pictures')) {
                                Log::warning('No files in upload request', ['request' => $request->all()]);

                                return response()->json(['error' => 'No se encontraron archivos para subir.'], 422);
                        }

                        foreach ((array)$files as $index => $file) {
                                if (!$file || !$file->isValid()) {
                                        Log::warning('Invalid file in upload', [
                                                'index'       => $index,
                                                'file_exists' => $file ? 'yes' : 'no',
                                                'is_valid'    => $file ? $file->isValid() : false,
                                                'error'       => $file ? $file->getErrorMessage() : 'File is null',
                                        ]);

                                        return response()->json(['error' => 'Archivo temporal no válido o expirado.'], 422);
                                }

                                $tempPath = $file->getRealPath();
                                if (!file_exists($tempPath)) {
                                        Log::warning('Temporary file missing', [
                                                'file' => $file->getClientOriginalName(),
                                                'path' => $tempPath,
                                                'size' => $file->getSize(),
                                        ]);

                                        return response()->json(['error' => 'El archivo temporal no se encuentra disponible.'], 422);
                                }
                        }
                }

                // Si no es AJAX (envío del formulario principal), usar las imágenes temporales guardadas
                if (!isFromAjax($request)) {
                        Log::info('Form submission - using saved temporary images', [
                                'saved_images_count' => count($savedPicturesInput),
                                'saved_images' => $savedPicturesInput,
                                'request_id' => $requestId,
                        ]);
                        
                        // Si hay imágenes temporales guardadas, usarlas
                        if (!empty($savedPicturesInput)) {
                                $picturesInput = $savedPicturesInput;
                                Log::info('Using saved temporary images for form submission', [
                                        'count' => count($picturesInput),
                                        'images' => $picturesInput,
                                        'request_id' => $requestId,
                                ]);
                        } else {
                                Log::warning('postForm called without files', [
                                        'from_ajax' => isFromAjax($request),
                                        'count'     => is_countable($files) ? count($files) : 0,
                                        'saved_images_count' => count($savedPicturesInput),
                                        'request_id' => $requestId,
                                ]);
                        }
                } else {
                        // Es AJAX - procesar archivos nuevos
                        if (!is_array($files) || count($files) === 0) {
                                Log::warning('postForm called without files', [
                                        'from_ajax' => isFromAjax($request),
                                        'count'     => is_countable($files) ? count($files) : 0,
                                ]);
                        }

                        if (is_array($files) && count($files) > 0) {
                                foreach ($files as $key => $file) {
                                        if (empty($file)) {
                                                continue;
                                        }

                                        $originalName = $file->getClientOriginalName();
                                        Log::debug('Processing upload', ['name' => $originalName]);

                                        $filePath = TmpUpload::image($file, $this->tmpUploadDir);

                                        if ($filePath instanceof JsonResponse) {
                                                return $filePath;
                                        }

                                        if (!is_string($filePath)) {
                                                Log::error('Image upload failed', [
                                                        'name' => $originalName,
                                                        'type' => gettype($filePath),
                                                ]);

                                                return response()->json(['error' => 'Imagen no válida.'], 422);
                                        }

                                        if ($filePath === null) {
                                                Log::error('Image upload failed', ['name' => $originalName]);
                                        } else {
                                                Log::info('Image uploaded', ['path' => $filePath]);

                                                $existing = session('picturesInput', []);
                                                if (!in_array($filePath, $existing)) {
                                                        $existing[] = $filePath;
                                                        session(['picturesInput' => $existing]);
                                                }
                                        }
                                        
                                        // Check the picture number limit
                                        if ($key >= ($picturesLimit - 1)) {
                                                break;
                                        }
                                }
                        }
                }
		
		// AJAX response
		$data = [];
		$data['initialPreview'] = [];
		$data['initialPreviewConfig'] = [];
		if (isFromAjax($request)) {
			if (is_array($picturesInput) && count($picturesInput) > 0 && isset($this->disk)) {
				foreach ($picturesInput as $key => $filePath) {
					if (empty($filePath)) {
						continue;
					}
					
					// $pictureUrl = thumbParam($filePath)->setOption('picture-md')->url();
					// $pictureUrl = hasTemporaryPath($filePath) ? $this->disk->url($filePath) : $pictureUrl;
					$pictureUrl = thumbService($filePath)->resize('picture-md')->url();
					$deleteUrl = url('posts/create/photos/' . $key . '/delete');
					
					try {
						$fileSize = $this->disk->exists($filePath) ? (int)$this->disk->size($filePath) : 0;
					} catch (Throwable $e) {
						$fileSize = 0;
					}
					
					// Build Bootstrap-FileInput plugin's parameters
					$data['initialPreview'][] = $pictureUrl;
					$data['initialPreviewConfig'][] = [
						'key'     => $key,
						'caption' => basename($filePath),
						'size'    => $fileSize,
						'url'     => $deleteUrl,
						'extra'   => ['id' => $key],
					];
				}
			}
			
			return response()->json($data);
		}
		
		// Redirect to the next page or Submit the form
		if (
			isset($this->countPackages, $this->countPaymentMethods)
			&& $this->countPackages > 0
			&& $this->countPaymentMethods > 0
			&& doesNoPackageOrPremiumOneSelected()
		) {
			if (is_array($picturesInput) && count($picturesInput) > 0) {
				flash(t('The pictures have been updated'))->success();
			}
			
			// Get the next URL
			$currentStep = $this->getStepByKey(get_class($this));
			$nextUrl = $this->getNextStepUrl($currentStep);
			
			return redirect()->to($nextUrl);
		} else {
			// Submit the form
			session()->flash('message', t('your_listing_is_created'));
			
			return $this->storeInputDataInDatabase($request);
		}
	}
	
	/**
	 * Remove a listing picture
	 *
	 * @param $pictureId
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function removePicture($pictureId, Request $request): JsonResponse|RedirectResponse
	{
		$picturesInput = session('picturesInput');
		
		$message = t('The picture cannot be deleted');
		$result = [
			'status'  => 0,
			'message' => $message,
		];
		
		if (isset($picturesInput[$pictureId])) {
			$res = true;
			try {
				$this->removePictureWithItsThumbs($picturesInput[$pictureId]);
			} catch (Throwable $e) {
				$res = false;
			}
			
			if ($res) {
				unset($picturesInput[$pictureId]);
				
				if (!empty($picturesInput)) {
					session()->put('picturesInput', $picturesInput);
				} else {
					session()->forget('picturesInput');
				}
				
				$message = t('The picture has been deleted');
				
				if (isFromAjax()) {
					$result['status'] = 1;
					$result['message'] = $message;
					
					return response()->json($result);
				} else {
					flash($message)->success();
					
					return redirect()->back();
				}
			}
		}
		
		if (isFromAjax()) {
			return response()->json($result);
		} else {
			flash($message)->error();
			
			return redirect()->back();
		}
	}
	
	/**
	 * Reorder the listing pictures
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
        public function reorderPictures(Request $request): JsonResponse
        {
                $httpStatus = 200;
                $result = ['status' => 0, 'message' => null];
		
		$params = $request->input('params');
		$stack = $params['stack'] ?? [];
		
		if (is_array($stack) && count($stack) > 0) {
			// Use unique ID to store post's pictures
			if (session()->has('uid')) {
				$this->tmpUploadDir = $this->tmpUploadDir . '/' . session('uid');
			}
			
			$statusOk = false;
			$newPicturesInput = [];
			foreach ($stack as $position => $item) {
				if (array_key_exists('caption', $item) && !empty($item['caption'])) {
					$newPicturesInput[] = $this->tmpUploadDir . '/' . $item['caption'];
					$statusOk = true;
				}
			}
			
			if ($statusOk) {
				session()->put('picturesInput', $newPicturesInput);
				$result['status'] = 1;
				$result['message'] = t('Your picture has been reorder successfully');
			} else {
				$result['error'] = 'The images have not been reordered.';
				$httpStatus = 400;
			}
		}
		
                return ajaxResponse()->json($result, $httpStatus);
        }

        public function uploadPhotos(Request $request): JsonResponse
        {
                $requestId = $request->header('X-Request-Id') ?? Str::uuid()->toString();
                $sessionId = session()->getId();
                
                // Log de configuración PHP solo una vez por request
                Log::debug('Upload request received', [
                        'request_id'   => $requestId,
                        'session_id'   => $sessionId,
                        'files_count'  => count($request->allFiles()),
                        'has_pictures' => $request->hasFile('pictures'),
                        'request_data' => $request->except(['_token', 'pictures'])
                ]);

                Log::debug('Detailed file analysis', [
                        'request_id'  => $requestId,
                        'total_files' => is_countable($request->file('pictures')) ? count($request->file('pictures')) : 0,
                        'file_details' => array_map(function ($file, $index) {
                                return [
                                        'index'           => $index,
                                        'name'            => $file->getClientOriginalName(),
                                        'size'            => $file->getSize(),
                                        'mime'            => $file->getMimeType(),
                                        'pathname'        => $file->getPathname(),
                                        'pathname_empty'  => empty($file->getPathname()),
                                        'realpath'        => $file->getRealPath(),
                                        'realpath_empty'  => empty($file->getRealPath()),
                                        'is_valid'        => $file->isValid(),
                                        'error_code'      => $file->getError(),
                                        'file_exists'     => file_exists($file->getPathname()),
                                        'is_readable'     => is_readable($file->getPathname()),
                                ];
                        }, is_array($request->file('pictures')) ? $request->file('pictures') : [], array_keys(is_array($request->file('pictures')) ? $request->file('pictures') : []))
                ]);

                if (!$request->hasFile('pictures')) {
                        Log::warning('No pictures in upload request');

                        return response()->json(['error' => 'No se encontraron archivos para subir.'], 422);
                }

                if (!session()->has('uid')) {
                        session()->put('uid', generateUniqueCode(9));
                }
                if (session()->has('uid')) {
                        $this->tmpUploadDir = $this->tmpUploadDir . '/' . session('uid');
                }

                $savedPicturesInput = (array)session('picturesInput');
                $picturesInput = [];
                $uploadedFiles = [];

                $defaultPicturesLimit = (int)config('settings.listing_form.pictures_limit', 5);
                $countExistingPictures = count($savedPicturesInput);
                $picturesLimit = $defaultPicturesLimit - $countExistingPictures;

                // Cache para evitar duplicados por nombre de archivo y tamaño
                $uploadedFileCache = [];
                foreach ($savedPicturesInput as $existingFile) {
                        $fileName = basename($existingFile);
                        $uploadedFileCache[$fileName] = true;
                }

                foreach ($request->file('pictures') as $index => $file) {
                        // Verificar duplicados antes de procesar
                        $originalName = $file->getClientOriginalName();
                        $fileKey = $originalName . '_' . $file->getSize();
                        
                        if (isset($uploadedFileCache[$fileKey])) {
                                Log::info('Duplicate file detected, skipping', [
                                        'request_id' => $requestId,
                                        'file' => $originalName,
                                        'size' => $file->getSize()
                                ]);
                                continue;
                        }
                        
                        Log::debug('Processing file', [
                                'request_id'    => $requestId,
                                'index'         => $index,
                                'original_name' => $originalName,
                                'temp_path'     => $file->getPathname(),
                                'real_path'     => $file->getRealPath(),
                                'size'          => $file->getSize(),
                                'is_valid'      => $file->isValid(),
                                'error'         => $file->getError(),
                                'file_exists'   => file_exists($file->getPathname()),
                                'is_readable'   => is_readable($file->getPathname()),
                        ]);

                        if (!$file->isValid()) {
                                Log::error('Invalid file detected', [
                                        'file'         => $file->getClientOriginalName(),
                                        'error_code'   => $file->getError(),
                                        'error_message'=> $file->getErrorMessage(),
                                ]);

                                return response()->json(['error' => 'Archivo inválido: ' . $file->getErrorMessage()], 422);
                        }

                        // Verificar que el archivo temporal existe y es legible
                        $tempPath = $file->getRealPath() ?: $file->getPathname();
                        if (empty($tempPath) || !file_exists($tempPath)) {
                                Log::error('Temporary file not accessible', [
                                        'file'         => $file->getClientOriginalName(),
                                        'temp_path'    => $file->getPathname(),
                                        'real_path'    => $file->getRealPath(),
                                ]);

                                return response()->json(['error' => 'No se puede acceder al archivo temporal. Intente subir el archivo nuevamente.'], 422);
                        }

                        if (!is_readable($tempPath)) {
                                Log::error('Temporary file not readable', [
                                        'file'         => $file->getClientOriginalName(),
                                        'temp_path'    => $tempPath,
                                ]);

                                return response()->json(['error' => 'No se puede leer el archivo temporal. Intente subir el archivo nuevamente.'], 422);
                        }

                        $filePath = TmpUpload::image($file, $this->tmpUploadDir);

                        if ($filePath instanceof JsonResponse) {
                                return $filePath;
                        }

                        if (!is_string($filePath)) {
                                Log::error('Image upload failed', [
                                        'name' => $file->getClientOriginalName(),
                                        'type' => gettype($filePath),
                                ]);

                                return response()->json(['error' => 'Imagen no válida.'], 422);
                        }

                        if ($filePath === null) {
                                Log::error('Image upload failed', [
                                        'request_id' => $requestId,
                                        'name' => $originalName
                                ]);
                        } else {
                                Log::info('Image uploaded', [
                                        'request_id' => $requestId,
                                        'path' => $filePath
                                ]);

                                // Marcar archivo como procesado para evitar duplicados
                                $uploadedFileCache[$fileKey] = true;

                                // Verificar que el archivo se guardó correctamente
                                $disk = StorageDisk::getDisk('local');
                                $fileExistsAfterSave = $disk->exists($filePath);
                                
                                if (!$fileExistsAfterSave) {
                                        Log::error('File not saved properly', [
                                                'request_id' => $requestId,
                                                'intended_path' => $filePath
                                        ]);
                                        return response()->json(['error' => 'No se pudo guardar el archivo temporal.'], 500);
                                }

                                $existing = session('picturesInput', []);
                                if (!in_array($filePath, $existing)) {
                                        $existing[] = $filePath;
                                        session(['picturesInput' => $existing]);
                                }
                                $uploadedFiles[] = [
                                        'original_name' => $originalName,
                                        'temp_name'     => basename($filePath),
                                        'temp_path'     => $filePath,
                                        'size'          => $file->getSize(),
                                        'mime_type'     => $file->getMimeType(),
                                ];
                        }

                        if ($index >= ($picturesLimit - 1)) {
                                break;
                        }
                }

                $newPicturesInput = array_merge($savedPicturesInput, $picturesInput);
                session()->put('picturesInput', $newPicturesInput);

                // Avoid deleting temporary files here. Cleanup will be
                // performed later by a scheduled command.

                return response()->json([
                        'message' => 'Archivos subidos correctamente',
                        'files'   => $uploadedFiles,
                ], 200);
        }
        
        private function cleanupOldTempFiles()
        {
                try {
                        $disk = StorageDisk::getDisk('local');
                        
                        // Limpiar archivos temporales de más de 1 hora de antigüedad
                        $tempDirectories = $disk->directories('temporary');
                        $now = time();
                        $cleanupCount = 0;
                        
                        foreach ($tempDirectories as $dir) {
                                $files = $disk->files($dir);
                                foreach ($files as $file) {
                                        $lastModified = $disk->lastModified($file);
                                        if (($now - $lastModified) >= 3600) { // 1 hora
                                                $disk->delete($file);
                                                $cleanupCount++;
                                        }
                                }
                                
                                // Si el directorio está vacío, eliminarlo también
                                if (empty($disk->files($dir))) {
                                        $disk->deleteDirectory($dir);
                                }
                        }
                        
                        if ($cleanupCount > 0) {
                                Log::info('Temporary files cleaned up', ['count' => $cleanupCount]);
                        }
                } catch (Throwable $e) {
                        Log::warning('Error during temporary files cleanup', [
                                'error' => $e->getMessage()
                        ]);
                }
        }
}
