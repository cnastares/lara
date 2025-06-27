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

namespace App\Http\Controllers\Api;

use App\Http\Requests\Front\PackageRequest;
use App\Http\Traits\LogsActivity;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * @group Payments
 */
class PaymentController extends BaseController
{
	use LogsActivity;
	
	protected PaymentService $paymentService;
	
	/**
	 * @param \App\Services\PaymentService $paymentService
	 */
	public function __construct(PaymentService $paymentService)
	{
		parent::__construct();
		
		$this->paymentService = $paymentService;
	}
	
	/**
	 * List payments
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam embed string Comma-separated list of the payment relationships for Eager Loading - Possible values: payable,paymentMethod,package,currency. Example: null
	 * @queryParam valid boolean Allow getting the valid payment list. Possible value: 0 or 1. Example: 0
	 * @queryParam active boolean Allow getting the active payment list. Possible value: 0 or 1. Example: 0
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: created_at. Example: created_at
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 *
	 * @param int|null $payableId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(?int $payableId = null): JsonResponse
	{
		$this->logRequestStart('payment.index', request()->only(['perPage', 'embed', 'valid', 'active']), 'payment');
		
		try {
			$params = [
				'perPage'     => (int)request()->input('perPage'),
				'embed'       => request()->input('embed'),
				'isValid'     => (request()->input('valid') == 1),
				'isActive'    => (request()->input('active') == 1),
				'paymentType' => request()->segment(3),
			];
			
			$result = $this->paymentService->getEntries($payableId, $params);
			
			$this->logSuccess('payment.index', [
				'payable_id' => $payableId,
				'filters' => $params,
				'result_count' => $result->getData()->data->meta->pagination->total ?? 0
			], 'payment');
			
			return $result;
		} catch (Throwable $e) {
			$this->logException('payment.index', $e, ['payable_id' => $payableId], 'payment');
			throw $e;
		}
	}
	
	/**
	 * Get payment
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam embed string Comma-separated list of the payment relationships for Eager Loading - Possible values: payable,paymentMethod,package,currency. Example: null
	 *
	 * @urlParam id int required The payment's ID. Example: 2
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): JsonResponse
	{
		$this->logRequestStart('payment.show', ['payment_id' => $id, 'embed' => request()->input('embed')], 'payment');
		
		try {
			$params = [
				'embed' => request()->input('embed'),
			];
			
			$result = $this->paymentService->getEntry($id, $params);
			
			$this->logSuccess('payment.show', [
				'payment_id' => $id,
				'embed' => $params['embed'],
				'found' => $result->getStatusCode() === 200
			], 'payment');
			
			return $result;
		} catch (Throwable $e) {
			$this->logException('payment.show', $e, ['payment_id' => $id], 'payment');
			throw $e;
		}
	}
	
	/**
	 * Store payment
	 *
	 * Note: This endpoint is only available for the multi steps form edition.
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam package int Selected package ID.
	 *
	 * @bodyParam country_code string required The code of the user's country. Example: US
	 * @bodyParam payable_id int required The payable's ID (ID of the listing or user). Example: 2
	 * @bodyParam payable_type string required The payable model's name - Possible values: Post,User. Example: Post
	 * @bodyParam package_id int required The package's ID (Auto filled when the query parameter 'package' is set).
	 * @bodyParam payment_method_id int The payment method's ID (required when the selected package's price is > 0). Example: 5
	 *
	 * @param \App\Http\Requests\Front\PackageRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(PackageRequest $request): JsonResponse
	{
		$inputData = $request->only(['country_code', 'payable_id', 'payable_type', 'package_id', 'payment_method_id']);
		$this->logRequestStart('payment.store', $inputData, 'payment');
		
		try {
			$params = [
				'payableType' => $request->input('payable_type'),
			];
			
			$result = $this->withPerformanceLog('payment.store', function() use ($request, $params) {
				return $this->paymentService->store($request, $params);
			}, 'payment');
			
			$this->logSuccess('payment.store', [
				'payable_id' => $request->input('payable_id'),
				'payable_type' => $request->input('payable_type'),
				'package_id' => $request->input('package_id'),
				'payment_method_id' => $request->input('payment_method_id'),
				'response_status' => $result->getStatusCode()
			], 'payment');
			
			return $result;
		} catch (Throwable $e) {
			$this->logException('payment.store', $e, $inputData, 'payment');
			throw $e;
		}
	}
}
