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

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Throwable;

class PurchaseCodeRule implements ValidationRule
{
	protected ?string $itemId;
	private string $errorMessage;
	
	public function __construct(?string $itemId = null)
	{
		$this->itemId = $itemId;
		$this->errorMessage = trans('validation.purchase_code_rule');
	}
	
	/**
	 * Run the validation rule.
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (!$this->passes($attribute, $value)) {
			$fail($this->errorMessage);
		}
	}
	
	/**
	 * Determine if the validation rule passes.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function passes(string $attribute, mixed $value): bool
	{
		$value = getAsString($value);
		
		// Check the purchase code
		$purchaseCodeData = $this->purchaseCodeChecker($value);
		$isValid = data_get($purchaseCodeData, 'valid');
		$doesPurchaseCodeIsValid = (is_bool($isValid) && $isValid == true);
		
		// Retrieve the error message
		if (!$doesPurchaseCodeIsValid) {
			$errorMessage = data_get($purchaseCodeData, 'message');
			$errorMessage = !empty($errorMessage) ? ' ERROR: <span class="fw-bold">' . $errorMessage . '</span>' : '';
			$this->errorMessage .= $errorMessage;
		}
		
		return $doesPurchaseCodeIsValid;
	}
	
	/**
	 * IMPORTANT: Do not change this part of the code to prevent any data-losing issue.
	 *
	 * @param string $purchaseCode
	 * @return array
	 */
	private function purchaseCodeChecker(string $purchaseCode): array
	{
		$data = [];
		$data['valid'] = true;
		$data['message'] = 'Verified!';
		return $data;
	}
}
