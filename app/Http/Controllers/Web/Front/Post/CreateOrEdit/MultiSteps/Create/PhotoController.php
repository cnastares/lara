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

                                if ($filePath === null) {
                                        Log::error('Image upload failed', ['name' => $originalName]);
                                } else {
                                        Log::info('Image uploaded', ['path' => $filePath]);

                                        $picturesInput[] = $filePath;
                                }
				
				// Check the picture number limit
				if ($key >= ($picturesLimit - 1)) {
					break;
				}
			}
			
			$newPicturesInput = array_merge($savedPicturesInput, $picturesInput);
			
			session()->put('picturesInput', $newPicturesInput);
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

        /**
         * Upload pictures asynchronously
         *
         * @param \Illuminate\Http\Request $request
         * @return \Illuminate\Http\JsonResponse
         */
        public function uploadPhotos(Request $request): JsonResponse
        {
                \Log::debug('PHP Upload Configuration', [
                        'upload_max_filesize' => ini_get('upload_max_filesize'),
                        'post_max_size'       => ini_get('post_max_size'),
                        'max_file_uploads'    => ini_get('max_file_uploads'),
                        'upload_tmp_dir'      => ini_get('upload_tmp_dir'),
                        'memory_limit'        => ini_get('memory_limit'),
                ]);

                \Log::debug('Upload request received', [
                        'files_count'  => count($request->allFiles()),
                        'has_pictures' => $request->hasFile('pictures'),
                        'request_data' => $request->except(['_token', 'pictures'])
                ]);

                \Log::debug('Detailed file analysis', [
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
                                ];
                        }, is_array($request->file('pictures')) ? $request->file('pictures') : [], array_keys(is_array($request->file('pictures')) ? $request->file('pictures') : []))
                ]);

                if (!$request->hasFile('pictures')) {
                        \Log::warning('No pictures in upload request');

                        return response()->json(['error' => 'No se encontraron archivos para subir.'], 422);
                }

                $uploadedFiles = [];

                foreach ($request->file('pictures') as $index => $file) {
                        \Log::debug('Processing file', [
                                'index'         => $index,
                                'original_name' => $file->getClientOriginalName(),
                                'temp_path'     => $file->getPathname(),
                                'size'          => $file->getSize(),
                                'is_valid'      => $file->isValid(),
                                'error'         => $file->getError(),
                        ]);

                        if (!$file->isValid()) {
                                \Log::error('Invalid file detected', [
                                        'file'         => $file->getClientOriginalName(),
                                        'error_code'   => $file->getError(),
                                        'error_message'=> $file->getErrorMessage(),
                                ]);

                                return response()->json(['error' => 'Archivo inválido: ' . $file->getErrorMessage()], 422);
                        }

                        try {
                                $tempPath = $file->getPathname();

                                // VALIDACIÓN CRÍTICA: Verificar que el path no esté vacío
                                if (empty($tempPath) || !is_string($tempPath)) {
                                        \Log::error('Empty or invalid temp path', [
                                                'file'       => $file->getClientOriginalName(),
                                                'path'       => $tempPath,
                                                'path_type'  => gettype($tempPath),
                                                'temp_name'  => $file->getFilename(),
                                                'size'       => $file->getSize(),
                                        ]);

                                        $tempPath = $file->getRealPath();
                                        if (empty($tempPath)) {
                                                return response()->json(['error' => 'No se puede acceder al archivo temporal: ' . $file->getClientOriginalName()], 422);
                                        }
                                }

                                if (!is_file($tempPath) || !is_readable($tempPath)) {
                                        \Log::warning('Temporary file missing or unreadable', [
                                                'file'     => $file->getClientOriginalName(),
                                                'path'     => $tempPath,
                                                'exists'   => file_exists($tempPath),
                                                'readable' => is_readable($tempPath),
                                                'size'     => $file->getSize(),
                                        ]);

                                        return response()->json(['error' => 'El archivo temporal no se encuentra disponible: ' . $file->getClientOriginalName()], 422);
                                }

                                // PROCESAR ARCHIVO MANUALMENTE sin usar Storage::putFileAs()
                                $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                                $tempStoragePath = storage_path('app/temp/' . $fileName);

                                $tempDir = dirname($tempStoragePath);
                                if (!is_dir($tempDir)) {
                                        mkdir($tempDir, 0755, true);
                                }

                                if (!copy($tempPath, $tempStoragePath)) {
                                        \Log::error('Failed to copy temporary file', [
                                                'source'        => $tempPath,
                                                'destination'   => $tempStoragePath,
                                                'source_exists' => file_exists($tempPath),
                                                'dest_writable' => is_writable($tempDir),
                                        ]);
                                        return response()->json(['error' => 'Error al procesar el archivo: ' . $file->getClientOriginalName()], 422);
                                }

                                if (!file_exists($tempStoragePath)) {
                                        \Log::error('File copy verification failed', [
                                                'destination' => $tempStoragePath,
                                                'source_size' => filesize($tempPath),
                                                'file'        => $file->getClientOriginalName(),
                                        ]);
                                        return response()->json(['error' => 'Error en la verificación del archivo copiado'], 422);
                                }

                                $uploadedFiles[] = [
                                        'original_name' => $file->getClientOriginalName(),
                                        'temp_name'     => $fileName,
                                        'temp_path'     => 'temp/' . $fileName,
                                        'full_path'     => $tempStoragePath,
                                        'size'          => $file->getSize(),
                                        'mime_type'     => $file->getMimeType(),
                                ];

                                \Log::info('File successfully processed manually', [
                                        'original'     => $file->getClientOriginalName(),
                                        'temp_name'    => $fileName,
                                        'size'         => filesize($tempStoragePath),
                                        'source_path'  => $tempPath,
                                        'destination'  => $tempStoragePath,
                                ]);

                        } catch (\Exception $e) {
                                \Log::error('Error processing file', [
                                        'file'  => $file->getClientOriginalName(),
                                        'error' => $e->getMessage(),
                                ]);

                                return response()->json(['error' => 'Error al procesar el archivo: ' . $e->getMessage()], 422);
                        }
                }

                $this->cleanupOldTempFiles();

                return response()->json([
                        'message' => 'Archivos subidos correctamente',
                        'files'   => $uploadedFiles,
                ], 200);
        }

        private function cleanupOldTempFiles()
        {
                $tempDir = storage_path('app/temp');
                if (!is_dir($tempDir)) {
                        return;
                }

                $files = glob($tempDir . '/*');
                $now = time();

                foreach ($files as $file) {
                        if (is_file($file) && ($now - filemtime($file)) >= 3600) {
                                unlink($file);
                                \Log::debug('Cleaned old temp file', ['file' => basename($file)]);
                        }
                }
        }
}
