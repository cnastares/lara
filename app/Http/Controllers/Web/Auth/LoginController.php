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

namespace App\Http\Controllers\Web\Auth;

use App\Helpers\Common\Cookie;
use App\Helpers\Common\JsonUtils;
use App\Http\Controllers\Web\Auth\Traits\Custom\CreateLoginSession;
use App\Http\Controllers\Web\Front\FrontController;
use App\Services\Auth\App\Http\Requests\LoginRequest;
use App\Services\Auth\LoginService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use Larapen\LaravelMetaTags\Facades\MetaTag;
use Illuminate\Support\Str;

class LoginController extends FrontController
{
	use CreateLoginSession;
	
	protected LoginService $loginService;
	
	// If not logged-in redirect to
	protected string $loginUrl;
	
	// After you've logged-in redirect to
	protected string $redirectTo;
	
	// After you've logged-out redirect to
	protected string $redirectAfterLogout;
	
	/**
	 * @param \App\Services\Auth\LoginService $loginService
	 */
	public function __construct(LoginService $loginService)
	{
		parent::__construct();
		
		$this->loginService = $loginService;
		
		// Check if the previous URL is from the admin panel area
		$isUrlFromAdminArea = str_contains(url()->previous(), urlGen()->adminUrl());
		
		// Update the Laravel login redirections URLs
		$this->loginUrl = urlGen()->signIn();
		if ($isUrlFromAdminArea) {
			$this->redirectTo = urlGen()->adminUrl();
			$this->redirectAfterLogout = urlGen()->signIn();
		} else {
			$this->redirectTo = urlGen()->accountOverview();
			$this->redirectAfterLogout = '/';
		}
	}
	
	/**
	 * Get the middleware that should be assigned to the controller.
	 */
	public static function middleware(): array
	{
		$array = [
			new Middleware('guest', except: ['logout']),
		];
		
		return array_merge(parent::middleware(), $array);
	}
	
	/**
	 * Show the application login form.
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function showLoginForm(): View|RedirectResponse
	{
		// Remembering Login
		if (function_exists('auth') && auth()->viaRemember()) {
			return redirect()->intended($this->redirectTo);
		}
		
		// Check verification notification
		if (!session()->has('flash_notification')) {
			if (session()->has('resendVerificationData')) {
				$resendVerificationData = JsonUtils::jsonToArray(session('resendVerificationData'));
				
				$isErrorOccurred = data_get($resendVerificationData, 'isErrorOccurred');
				$field = data_get($resendVerificationData, 'field');
				$fieldHiddenValue = data_get($resendVerificationData, 'fieldHiddenValue');
				
				$message = ($field == 'phone')
					? trans('auth.verification_code_sent', ['fieldHiddenValue' => $fieldHiddenValue])
					: trans('auth.verification_link_sent', ['fieldHiddenValue' => $fieldHiddenValue]);
				
				if ($isErrorOccurred) {
					flash($message)->error();
				} else {
					flash($message)->info();
				}
			}
		}
		
		$coverTitle = trans('auth.login_cover_title');
		$coverDescription = trans('auth.login_cover_description');
		
		// Meta Tags
		[$title, $description, $keywords] = getMetaTag('login');
		MetaTag::set('title', $title);
		MetaTag::set('description', strip_tags($description));
		MetaTag::set('keywords', $keywords);
		
		return view('auth.login.index', compact('coverTitle', 'coverDescription'));
	}
	
	/**
	 * @param \App\Services\Auth\App\Http\Requests\LoginRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function login(LoginRequest $request): RedirectResponse
	{
		// Generar o recuperar request-id
		$requestId = $request->header('X-Request-Id') ?? Str::uuid()->toString();
		// Log-in the user
		$data = getServiceData($this->loginService->login($request));
		// Parsing the API response
		$message = data_get($data, 'message');
		$success = data_get($data, 'success');
		// Two-Factor Authentication Challenge Checking
		$user = data_get($data, 'result');
		$isTwoFactorEnabled = (isTwoFactorEnabled() && data_get($user, 'two_factor_enabled', false));
		if ($isTwoFactorEnabled) {
			$isTwoFactorSendCodeFailed = (data_get($data, 'extra.sendCodeFailed') === true);
			if ($isTwoFactorSendCodeFailed) {
				session()->forget('twoFactorUserId');
				session()->put('twoFactorAuthenticated', true);
			} else {
				$userId = data_get($user, 'id');
				$twoFactorSuccess = data_get($data, 'extra.twoFactorSuccess');
				$twoFactorChallengeRequired = data_get($data, 'extra.twoFactorChallengeRequired');
				$isTwoFactorChallengeRequired = ($success === false && $twoFactorChallengeRequired === true);
				$twoFactorMethodValue = data_get($data, 'extra.twoFactorMethodValue');
				if ($isTwoFactorChallengeRequired && !empty($userId) && !empty($twoFactorMethodValue)) {
					session()->put('twoFactorUserId', $userId);
					session()->put('twoFactorMethodValue', $twoFactorMethodValue);
					if ($twoFactorSuccess) {
						flash($message)->success();
					} else {
						flash($message)->error();
					}
					return redirect()->to(urlGen()->twoFactorChallenge());
				}
			}
		}
		if ($success) {
			$userId = null;
			if (function_exists('auth')) {
				$user = auth()->user();
				$userId = $user ? $user->getAuthIdentifier() : null;
			}
			Log::info('User logged in', [
				'user_id' => $userId,
				'ip' => $request->ip(),
				'request_id' => $requestId,
			]);
			return $this->createNewSession($data);
		}
		// Loguear error de login
		Log::warning('User login failed', [
			'ip' => $request->ip(),
			'request_id' => $requestId,
			'message' => $message,
			'input' => $request->except(['password']),
		]);
		$message = $message ?? trans('auth.failed');
		return redirect()->to($this->loginUrl)->withErrors(['error' => $message])->withInput();
	}
	
	/**
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function logout(): RedirectResponse
	{
		$userId = auth()->user()?->getAuthIdentifier() ?? '-1';
		
		// Log-out the user
		$data = getServiceData($this->loginService->logout($userId));
		
		// Parsing the API response
		$message = data_get($data, 'message');
		
                if (data_get($data, 'success')) {
                        Log::info('User logged out', [
                                'user_id' => $userId,
                                'ip' => request()->ip(),
                        ]);

                        // Log out the user on a web client (Browser)
                        logoutSession($message);

                        // Reset Dark Mode
                        Cookie::forget('darkTheme');
                } else {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
		}
		
		$uriPath = property_exists($this, 'redirectAfterLogout') ? $this->redirectAfterLogout : '/';
		
		return redirect()->to($uriPath);
	}
	
	/**
	 * Check user session status (AJAX endpoint)
	 * 
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function checkSession(): \Illuminate\Http\JsonResponse
	{
		$user = auth()->user();
		$isAuthenticated = !is_null($user);
		
		$response = [
			'authenticated' => $isAuthenticated,
			'status' => $isAuthenticated ? 'authenticated' : 'unauthenticated',
			'timestamp' => now()->toISOString(),
		];
		
		if ($isAuthenticated) {
			$response['user'] = [
				'id' => $user->getAuthIdentifier(),
				'name' => $user->name ?? null,
				'email' => $user->email ?? null,
			];
			
			// Información adicional de sesión si está disponible
			if (session()->getId()) {
				$response['session_id'] = session()->getId();
			}
			
			// Tiempo de vida de la sesión
			$lifetime = config('session.lifetime', 120) * 60; // en segundos
			$response['expires_at'] = now()->addSeconds($lifetime)->toISOString();
		}
		
		return response()->json($response);
	}
}
