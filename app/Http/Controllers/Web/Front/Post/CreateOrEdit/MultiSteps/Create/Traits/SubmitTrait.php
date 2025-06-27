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

namespace App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Create\Traits;

use App\Helpers\Common\Files\Upload;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Create\FinishController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Create\PostController;
use App\Http\Requests\Front\PackageRequest;
use App\Http\Requests\Front\PhotoRequest;
use App\Http\Requests\Front\PostRequest;
use App\Models\CategoryField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait SubmitTrait
{
	/**
	 * Store all input data in database
	 *
	 * @param \App\Http\Requests\Front\PostRequest|\App\Http\Requests\Front\PhotoRequest|\App\Http\Requests\Front\PackageRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	protected function storeInputDataInDatabase(PostRequest|PhotoRequest|PackageRequest $request): RedirectResponse
	{
		$requestId = $request->header('X-Request-Id') ?? Str::uuid()->toString();
		
		// Get all saved input data
		$postInput = (array)session('postInput');
		$picturesInput = (array)session('picturesInput');
		$paymentInput = (array)session('paymentInput');
		
		// AGREGAR LOG: Verificar estructura de la tabla (una sola vez)
		Log::info('Database table structure verification', [
			'table_columns' => Schema::getColumnListing('posts'),
			'image_related_columns' => array_filter(Schema::getColumnListing('posts'), function($column) {
				return strpos(strtolower($column), 'image') !== false || strpos(strtolower($column), 'photo') !== false || strpos(strtolower($column), 'picture') !== false;
			}),
			'request_id' => $requestId,
		]);
		
		// AGREGAR LOG: Verificar configuración de storage
		Log::info('Storage configuration verification', [
			'default_disk' => config('filesystems.default'),
			'local_disk_config' => config('filesystems.disks.local'),
			'public_disk_config' => config('filesystems.disks.public'),
			'storage_app_path' => storage_path('app'),
			'storage_app_exists' => is_dir(storage_path('app')),
			'storage_app_writable' => is_writable(storage_path('app')),
			'temporary_directory' => storage_path('app/temporary'),
			'temporary_dir_exists' => is_dir(storage_path('app/temporary')),
			'temporary_dir_writable' => is_writable(storage_path('app/temporary')),
			'public_storage_path' => storage_path('app/public'),
			'public_storage_exists' => is_dir(storage_path('app/public')),
			'public_storage_writable' => is_writable(storage_path('app/public')),
			'request_id' => $requestId,
		]);
		
		// AGREGAR LOG: Verificar tablas relacionadas con imágenes
		Log::info('Checking related image tables', [
			'pictures_table_exists' => Schema::hasTable('pictures'),
			'post_pictures_table_exists' => Schema::hasTable('post_pictures'),
			'uploads_table_exists' => Schema::hasTable('uploads'),
			'files_table_exists' => Schema::hasTable('files')
		]);
		
		if (Schema::hasTable('pictures')) {
			Log::info('Pictures table structure', [
				'pictures_columns' => Schema::getColumnListing('pictures')
			]);
		}
		
		// AGREGAR LOG: Verificar configuración de storage para archivos temporales
		Log::info('Temporary file storage verification', [
			'local_disk_root' => config('filesystems.disks.local.root'),
			'public_disk_root' => config('filesystems.disks.public.root'),
			'temp_files_in_local' => Storage::disk('local')->files('temporary'),
			'temp_files_in_public' => Storage::disk('public')->files('temporary'),
			'all_temp_files_local' => Storage::disk('local')->allFiles('temporary'),
			'all_temp_files_public' => Storage::disk('public')->allFiles('temporary')
		]);
		
		// AGREGAR LOG: Estado inicial de los datos
		Log::info('Starting storeInputDataInDatabase', [
			'post_input_count' => count($postInput),
			'pictures_input_count' => count($picturesInput),
			'pictures_input' => $picturesInput,
			'payment_input_count' => count($paymentInput),
		]);
		
		if (empty($postInput)) {
			$postStep = $this->getStepByKey(PostController::class);
			$postStepUrl = $this->getNextStepUrl($postStep);
			
			Log::warning('No post input found, redirecting to post step');
			return redirect()->to($postStepUrl);
		}
		
		// Create the global input to send for database saving
		$inputArray = $postInput;
		if (isset($inputArray['category_id'], $inputArray['cf'])) {
			$fields = CategoryField::getFields($inputArray['category_id']);
			if ($fields->count() > 0) {
				foreach ($fields as $field) {
					if ($field->type == 'file') {
						$cfFilePath = $inputArray['cf'][$field->id] ?? null;
						if (!empty($cfFilePath)) {
							if (hasTemporaryPath($cfFilePath)) {
								$inputArray['cf'][$field->id] = Upload::fromPath($cfFilePath);
							}
						}
					}
				}
			}
		}
		
		// AGREGAR LOG: ANTES de procesar las imágenes
		Log::info('Starting image processing in storeInputDataInDatabase', [
			'pictures_input_count' => count($picturesInput ?? []),
			'pictures_input' => $picturesInput ?? [],
		]);
		
                $inputArray['pictures'] = [];
                $uploadedFiles = [];
                if (!empty($picturesInput)) {
                        foreach ($picturesInput as $index => $filePath) {
				// AGREGAR LOG: DURANTE el procesamiento de cada imagen
				Log::info('Processing individual image', [
					'index' => $index,
					'temp_path' => $filePath,
					'temp_file_exists' => Storage::disk('local')->exists($filePath),
					'temp_file_size' => Storage::disk('local')->exists($filePath) ? Storage::disk('local')->size($filePath) : 'file_not_found',
					'has_temporary_path' => hasTemporaryPath($filePath),
				]);
				
				// AGREGAR LOG: Análisis de timing
				Log::info('Timing analysis', [
					'current_time' => now(),
					'temp_file_path' => $filePath,
					'temp_file_exists' => Storage::disk('local')->exists($filePath),
					'temp_directory_contents' => Storage::disk('local')->files('temporary'),
					'all_temporary_files' => Storage::disk('local')->allFiles('temporary')
				]);
				
				if (!Storage::disk('local')->exists($filePath)) {
					// NUEVO: Intentar recuperar de otras ubicaciones
					Log::warning('Temporary file not found, attempting recovery', [
						'original_path' => $filePath,
						'attempting_alternatives' => true
					]);
					
					// Intentar encontrar en todas las ubicaciones posibles
					$allTempFiles = Storage::disk('local')->allFiles('temporary');
					$possibleMatches = array_filter($allTempFiles, function($file) use ($filePath) {
						return basename($file) === basename($filePath);
					});
					
					Log::info('Recovery attempt results', [
						'all_temp_files' => $allTempFiles,
						'possible_matches' => $possibleMatches,
						'recovery_successful' => !empty($possibleMatches)
					]);
					
					if (!empty($possibleMatches)) {
						$filePath = $possibleMatches[0]; // Usar el primer match
						Log::info('Using recovered file', ['recovered_path' => $filePath]);
					} else {
						Log::error('Unable to recover temporary file', [
							'original_path' => $filePath,
							'available_files' => $allTempFiles
						]);
						continue; // Saltar esta imagen
					}
				}
				
				if (!empty($filePath)) {
                                        if (hasTemporaryPath($filePath)) {
                                                try {
                                                        $uploadedFile = Upload::fromPath($filePath);
                                                        $inputArray['pictures'][] = $uploadedFile;
                                                        $uploadedFiles[] = $uploadedFile;
							
							Log::info('Successfully created Upload object from path', [
								'index' => $index,
								'original_path' => $filePath,
								'upload_object_type' => get_class($uploadedFile),
								'upload_object_valid' => $uploadedFile->isValid(),
							]);
						} catch (\Exception $e) {
							Log::error('Error creating Upload object from path', [
								'index' => $index,
								'path' => $filePath,
								'error' => $e->getMessage(),
								'file' => $e->getFile(),
								'line' => $e->getLine(),
							]);
						}
					} else {
						Log::warning('File path is not recognized as temporary', [
							'index' => $index,
							'path' => $filePath,
						]);
					}
				}
			}
		}
		
		// AGREGAR LOG: DESPUÉS de procesar todas las imágenes
		Log::info('Completed image processing', [
			'final_pictures_count' => count($inputArray['pictures']),
			'final_pictures_array' => array_map(function($file) {
				return [
					'type' => get_class($file),
					'original_name' => $file->getClientOriginalName(),
					'is_valid' => $file->isValid(),
					'size' => $file->getSize(),
				];
			}, $inputArray['pictures']),
		]);
		
		$inputArray = array_merge($inputArray, $paymentInput);
		
                $request->merge($inputArray);

                // Set the pictures files in the current request (from the saved input variable)
                // Note: In that case file needs to be retrieved using $request->files->all() instead of $request->allFiles()
                if (!empty($uploadedFiles)) {
                        $request->files->set('pictures', $uploadedFiles);

                        Log::info('Set pictures in request files', [
                                'pictures_count' => count($uploadedFiles),
                                'request_has_pictures' => $request->hasFile('pictures'),
                                'request_files_count' => count($request->allFiles()),
                        ]);

                        Log::debug('Pictures in request', [
                                'count' => $request->files->count(),
                                'keys'  => array_keys($request->files->all()),
                        ]);
                }
		
		// AGREGAR LOG: ANTES de guardar en BD
		Log::info('About to call postService->store', [
			'request_has_pictures' => $request->hasFile('pictures'),
			'request_files_count' => count($request->allFiles()),
			'input_array_keys' => array_keys($inputArray),
		]);
		
		// Store the post
		try {
			$data = getServiceData($this->postService->store($request));
			
			// AGREGAR LOG: DESPUÉS de guardar en BD
			Log::info('Post service store completed', [
				'success' => data_get($data, 'success'),
				'post_id' => data_get($data, 'result.id'),
				'message' => data_get($data, 'message'),
				'result_keys' => array_keys(data_get($data, 'result', [])),
			]);
			
		} catch (\Exception $e) {
			Log::error('Error in postService->store', [
				'error_message' => $e->getMessage(),
				'error_file' => $e->getFile(),
				'error_line' => $e->getLine(),
				'pictures_input' => $picturesInput ?? [],
			]);
			throw $e;
		}
		
		// dd($data);
		
		// Parsing the API response
		$message = data_get($data, 'message');
		
		// Get the listing ID
		$postId = data_get($data, 'result.id');
		
		// Notification Message
		if (data_get($data, 'success')) {
			session()->put('message', $message);
			
			// Save the listing's ID in session
			if (!empty($postId)) {
				$request->session()->put('postId', $postId);
			}
			
			// Clear Temporary Inputs & Files
			$this->clearTemporaryInput();
			
			Log::info('Post created successfully', [
				'post_id' => $postId,
				'message' => $message,
			]);
		} else {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
			
			Log::error('Post creation failed', [
				'message' => $message,
				'data' => $data,
			]);
			
			$previousUrl = data_get($data, 'extra.previousUrl');
			if (!empty($previousUrl)) {
				return redirect()->to($previousUrl)->withInput($request->except('pictures'));
			} else {
				return redirect()->back()->withInput($request->except('pictures'));
			}
		}
		
		// Get Listing Resource
		$post = data_get($data, 'result');
		
		abort_if(empty($post), 404, t('post_not_found'));
		
		// AGREGAR LOG: Verificar el post creado
		Log::info('Post resource retrieved', [
			'post_id' => data_get($post, 'id'),
			'post_attributes' => array_keys(data_get($post, 'attributes', [])),
			'post_has_pictures' => isset($post['pictures']),
			'post_pictures_count' => isset($post['pictures']) ? count($post['pictures']) : 0,
		]);
		
		// Get the next URL
		$nextStep = $this->getStepByKey(FinishController::class);
		$nextUrl = $this->getStepUrl($nextStep);
		
		if (!empty($paymentInput)) {
			// Check if the payment process has been triggered
			// NOTE: Payment bypass email or phone verification
			// ===| Make|send payment (if needed) |==============
			
			$postObj = $this->retrievePayableModel($request, $postId);
			if (!empty($postObj)) {
				$payResult = $this->isPaymentRequested($request, $postObj);
				if (data_get($payResult, 'success')) {
					return $this->sendPayment($request, $postObj);
				}
				if (data_get($payResult, 'failure')) {
					flash(data_get($payResult, 'message'))->error();
				}
			}
			
			// ===| If no payment is made (continue) |===========
		}
		
		// Get user's verification data
		$vEmailData = data_get($data, 'extra.sendEmailVerification');
		$vPhoneData = data_get($data, 'extra.sendPhoneVerification');
		$isUnverifiedEmail = (bool)(data_get($vEmailData, 'extra.isUnverifiedField') ?? false);
		$isUnverifiedPhone = (bool)(data_get($vPhoneData, 'extra.isUnverifiedField') ?? false);
		
		if ($isUnverifiedEmail || $isUnverifiedPhone) {
			// Save the Next URL before verification
			session()->put('itemNextUrl', $nextUrl);
			
			if ($isUnverifiedEmail) {
				// Create Notification Trigger
				$resendEmailVerificationData = data_get($vEmailData, 'extra');
				session()->put('resendEmailVerificationData', collect($resendEmailVerificationData)->toJson());
			}
			
			if ($isUnverifiedPhone) {
				// Create Notification Trigger
				$resendPhoneVerificationData = data_get($vPhoneData, 'extra');
				session()->put('resendPhoneVerificationData', collect($resendPhoneVerificationData)->toJson());
				
				// Phone Number verification
				// Get the token|code verification form page URL
				// The user is supposed to have received this token|code by SMS
				$nextUrl = urlGen()->phoneVerification('posts');
			}
		}
		
		$nextUrl = urlQuery($nextUrl)
			->setParameters(request()->only(['packageId']))
			->toString();
		
		// Get mail sending data
		$mailData = data_get($data, 'extra.mail');
		
		// Mail Notification Message
		if (data_get($mailData, 'message')) {
			$mailMessage = data_get($mailData, 'message');
			if (data_get($mailData, 'success')) {
				flash($mailMessage)->success();
			} else {
				flash($mailMessage)->error();
			}
		}
		
		Log::info('storeInputDataInDatabase completed successfully', [
			'post_id' => $postId,
			'next_url' => $nextUrl,
		]);
		
		return redirect()->to($nextUrl);
	}
}
