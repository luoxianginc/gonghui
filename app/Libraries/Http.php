<?php

namespace App\Libraries;

class Http
{
	public static function httpGet($url, $data = null)
	{
		if (!is_null($data)) {
			$url .= '?' . urldecode(http_build_query($data));
		}

		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Accept: */*',
			'User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)',
			'Connection: Keep-Alive'
		]);

		if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		}

		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

		$response = curl_exec($ch);
		curl_close($ch);

		return $response;
	}

	public static function httpPost($url, $data = [], $timeout = 30)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

		$response = curl_exec($ch);

		if ($error = curl_error($ch)) {
			die($error);
		}

		curl_close($ch);

		return $response;
	}

	public static function signature($requestName, $arr) {
		if (!is_array($arr)) {
			return false;
		}

		ksort($arr);

		$str = $requestName;
		foreach ($arr as $key => $value) {
			$str .= "{$key}{$value}";
		}
		$str .= '9d381db3cbbe37b5f48780b9d528e27a';

		return md5($str);
	}

	public static function isMobile() {
		if (!empty($_SERVER['HTTP_USER_AGENT'])) {
			if (
				strpos($_SERVER['HTTP_USER_AGENT'], 'Android') ||
				strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') ||
				strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')
			) {
				return true;
			}
		}

		return false;
	}

	public static function responseDone($data = [], $code = 200)
	{
		$res = ['meta' => ['code' => $code]];

		if (!empty($data)) {
			$res['data'] = $data;
		}

		return $res;
	}

	public static function responseFail($errorMsg = "参数错误", $code = 400, $errorType = "param_error")
	{
		return [
			'meta' => [
				'code'			=> $code,
				'error_type'	=> $errorType,
				'error_message'	=> $errorMsg,
			],
		];
	}

	public static function sendMessage($mobile, $content)
	{
		$data = [
			'userid'	=> env('MSG_USERID'),
			'password'	=> env('MSG_PASSWORD'),
			'account'	=> env('MSG_ACCOUNT'),
			'content'	=> $content,
			'mobile'	=> $mobile,
			'sendtime'	=> '' 
		];

		$url=env('MSG_HOST');
		$response = self::httpPost($url, $data);

		return strpos($response, 'Success');
	}
}
