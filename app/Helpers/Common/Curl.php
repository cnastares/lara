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

namespace App\Helpers\Common;

use Throwable;

class Curl
{
	public static string $userAgent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3";
	public static array $httpHeader = [
		'Accept-Charset: utf-8',
		'Accept-Language: en-us,en;q=0.7,bn-bd;q=0.3',
	];
	
	/**
	 * @param $url
	 * @param $cookieFile
	 * @param $postData
	 * @param $referrerUrl
	 * @return bool|string
	 */
	public static function fetch($url, $cookieFile = null, $postData = null, $referrerUrl = null)
	{
		// Use PHP 'file_get_contents' function if cURL is not enable
		if (!function_exists('curl_init') || !function_exists('curl_exec')) {
			return self::fileGetContents($url);
		}
		
		// Use cURL
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, $url);
		
		if (!empty($postData)) {
			if (is_array($postData)) {
				$postData = Arr::query($postData);
			}
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); // Set the post data
			curl_setopt($ch, CURLOPT_POST, 1); // This is a POST query
		}
		
		curl_setopt($ch, CURLOPT_HEADER, 0);
		
		if (str_contains(strtolower($url), 'https://')) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // To disable SSL Cert checks
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, self::$httpHeader);
		curl_setopt($ch, CURLOPT_USERAGENT, self::$userAgent);
		
		if (!empty($referrerUrl)) {
			curl_setopt($ch, CURLOPT_REFERER, $referrerUrl);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // We want the content after the query
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Follow Location redirects
		
		if (!empty($cookieFile)) {
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile); // Read cookie information
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile); // Write cookie information
		}
		
		$buffer = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		
		if ($buffer) {
			return $buffer;
		} else {
			return $error;
		}
	}
	
	/**
	 * @param $url
	 * @return string
	 */
	public static function fileGetContents($url): string
	{
		try {
			$buffer = file_get_contents($url);
		} catch (Throwable $e) {
			$buffer = $e->getMessage();
			if (empty($buffer)) {
				$buffer = t('unknown_error');
			}
		}
		
		return $buffer;
	}
	
	/**
	 * @param $url
	 * @param $saveTo
	 * @param string|null $cookieFile
	 */
	public static function grabFile($url, $saveTo, string $cookieFile = null)
	{
		$url = str_replace(['&amp;'], ['&'], $url);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if (str_contains(strtolower($url), 'https://')) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}
		curl_setopt($ch, CURLOPT_USERAGENT, self::$userAgent);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if (!empty($cookieFile)) {
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile); // Read cookie information
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile); // Write cookie information
		}
		
		$buffer = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		
                if ($buffer) {
                        if (file_exists($saveTo)) {
                                unlink($saveTo);
                        }
                        if (!self::filePutContents($saveTo, $buffer)) {
                                throw new \RuntimeException($url . " doesn't save at " . $saveTo . ".\n");
                        }
                } else {
                        throw new \RuntimeException($error);
                }
        }
	
	/**
	 * @param $url
	 * @param null $cookies
	 * @return string|bool
	 */
	public static function getContent($url, $cookies = null)
	{
		$ch = curl_init();
		if (str_contains(strtolower($url), 'https://')) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // To disable SSL Cert checks
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}
		curl_setopt($ch, CURLOPT_USERAGENT, self::$userAgent);
		if (!empty($cookies)) {
			curl_setopt($ch, CURLOPT_COOKIE, $cookies);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		
		$buffer = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		
		if ($buffer) {
			return $buffer;
		} else {
			return $error;
		}
	}
	
	/**
	 * @param $url
	 * @param $params
	 * @param null $cookies
	 * @return string|bool
	 */
	public static function getContentByForm($url, $params, $cookies = null)
	{
		$ch = curl_init();
		if (str_contains(strtolower($url), 'https://')) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // To disable SSL Cert checks
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}
		curl_setopt($ch, CURLOPT_USERAGENT, self::$userAgent);
		if (!empty($cookies)) {
			curl_setopt($ch, CURLOPT_COOKIE, $cookies);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		
		$buffer = curl_exec($ch);
		curl_close($ch);
		
		return $buffer;
	}
	
	/**
	 * @param $url
	 * @return string|null
	 */
	public static function getCookies($url): ?string
	{
		$ch = curl_init();
		if (str_contains(strtolower($url), 'https://')) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // To disable SSL Cert checks
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}
		curl_setopt($ch, CURLOPT_USERAGENT, self::$userAgent);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		
		$buffer = curl_exec($ch);
		curl_close($ch);
		
		preg_match_all('|Set-Cookie: (.*);|U', $buffer, $tmp);
		if (isset($tmp[1]) && !empty($tmp[1])) {
			$cookies = implode(';', $tmp[1]);
		} else {
			$cookies = null;
		}
		
		return $cookies;
	}
	
	/**
	 * @param $filename
	 * @param $data
	 * @param int $flags
	 * @param null $context
	 */
        public static function filePutContents($filename, $data, int $flags = 0, $context = null): bool
        {
		$tmp = explode('/', $filename);
		$shortFilename = array_pop($tmp);
		
		$filePath = '';
		foreach ($tmp as $path) {
			$filePath .= '/' . $path;
			if (!is_dir($filePath)) {
				mkdir($filePath);
			}
		}
		
		$filename = $filePath . '/' . $shortFilename;
		
                return file_put_contents($filename, $data, $flags, $context) !== false;
        }
}
