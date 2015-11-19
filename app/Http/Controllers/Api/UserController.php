<?php

namespace App\Http\Controllers\Api;

use Mail;
use PRedis;
use Illuminate\Http\Request;
use App\Libraries\String;
use App\Libraries\Http;
use App\Models\User;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
	/*
	 * 登录 post /users/access_token
	 *
	 * 参数
	 *   mobile
	 *	 verification
	 *   timestamp
	 *   nonce
	 *   signature
	 * ---------------
	 *   username/mobile
	 *   password
	 *   timestamp
	 *   nonce
	 *   signature
	 *
	 * 返回值
	 *   {
	 *       "meta": {"code": 200},
	 *	     "data": {"access_token": "99f3322d8c2c31362529c31436fee1b7"}
	 *	 }
	 */
	public function login(Request $request) 
	{
		$mobile			= $request->input('mobile');
		$verification	= $request->input('verification');
		$username		= $request->input('username');
		$password		= $request->input('password');
		$timestamp		= $request->input('timestamp');
		$nonce			= $request->input('nonce');
		$signature		= $request->input('signature');
		$createdIp		= $request->server('REMOTE_ADDR');

		$type = collect(compact('mobile', 'username'))->filter(function($item){
			return !empty($item);
		})->keys()->first();

		if (!$type || !$timestamp || !$nonce || !$signature) {
			return response()->json(Http::responseFail());
		}

		switch ($type) {
			case 'mobile':
				$mode = collect(compact('verification', 'password'))->filter(function($item){
					return !empty($item);
				})->keys()->first();

				if (!$mode || !String::isMobile($mobile)) {
					return response()->json(Http::responseFail());
				}
				
				switch ($mode) {
					case 'verification':
						$sign = Http::signature('users/access_token', compact($type, 'verification', 'timestamp', 'nonce'));
						// return response()->json($sign);

						if ($sign != $signature) {
							return response()->json(Http::responseFail('非法请求', 405, 'request_error'));
						}
					
						if ($verification != PRedis::get("verification:{$mobile}")) {
							return response()->json(Http::responseFail('验证码错误'));
						}

						$user = User::findByMobile($mobile);

						if ($user->isEmpty()) {
							$info['password'] = rand(100000, 999999);
							$user = User::register('mobile', $mobile, $createdIp, $info);
							$content = "您的号码:{$mobile}，初始密码为{$info['password']}。【阿达游戏】";
							Http::sendMessage($mobile, $content);
						}

						PRedis::delete("verification:{$mobile}");

						break;

					case 'password':
						$sign = Http::signature('users/access_token', compact($type, 'password', 'timestamp', 'nonce'));
						// return response()->json($sign);

						if ($sign != $signature) {
							return response()->json(Http::responseFail('非法请求', 405, 'request_error'));
						}

						$user = User::findByMobile($mobile);

						if ($user->isEmpty() || $user->password != String::md5Salt($password, $user->getId())) {
							return response()->json(Http::responseFail('帐号或密码错误'));
						}

						break;
				}

				break;

			case 'username': 
				if (!$password) {
					return response()->json(Http::responseFail('密码为空'));
				}

				$sign = Http::signature('users/access_token', compact($type, 'password', 'timestamp', 'nonce'));
				// return response()->json($sign);

				if ($sign != $signature) {
					return response()->json(Http::responseFail('非法请求', 405, 'request_error'));
				}

				$user = User::findByUsername($username);

				if ($user->isEmpty() || $user->password != String::md5Salt($password, $user->getId())) {
					return response()->json(Http::responseFail('帐号或密码错误'));
				}

				break;
		}

		return response()->json(Http::responseDone($user->access_token));
	}

	/*
	 * 注册 post /users
	 *
	 * 参数
	 *   username
	 *   password
	 *   timestamp
	 *   nonce
	 *   signature
	 *
	 * 返回值
	 *   {
	 *       "meta": {"code": 200},
	 *	     "data": {"access_token": "99f3322d8c2c31362529c31436fee1b7"}
	 *	 }
	 */
	public function register(Request $request) 
	{
		$username	= $request->input('username');
		$password	= $request->input('password');
		$timestamp	= $request->input('timestamp');
		$nonce		= $request->input('nonce');
		$signature	= $request->input('signature');
		$createdIp	= $request->server('REMOTE_ADDR');

		if (!$username || !$password || !$timestamp || !$nonce || !$signature) {
			return response()->json(Http::responseFail());
		} elseif (strpos($username, '@')) {
			return response()->json(Http::responseFail('帐号包含@符'));
		} elseif (preg_match('/^[0-9]+$/', $username)) {
			return response()->json(Http::responseFail('帐号全是数字'));
		} elseif (PRedis::hExists('usernames', $username)) {
			return response()->json(Http::responseFail('帐号已被注册', 412, 'param_error'));
		} elseif (strlen($password) < 6 || strlen($password) > 30) {
			return response()->json(Http::responseFail('密码位数须在6~30之间'));
		}

		$sign = Http::signature('users', compact('username', 'password', 'timestamp', 'nonce'));
		// return response()->json($sign);

		if ($sign != $signature) {
			return response()->json(Http::responseFail('非法请求', 405, 'request_error'));
		}

		$user = User::register('username', $username, $createdIp, ['name' => $username, 'password' => $password]);

		return response()->json(Http::responseDone($user->access_token));
	}

	/*
	 * 修改用户密码 post /users/password
	 *
	 * 参数
	 *   account 
	 *   old_password
	 *   password
	 *
	 * 返回值
	 *   {"meta": {"code": 200}}
	 */
	public function changePassword(Request $request) 
	{
		$account		= $request->input('account');
		$oldPassword	= $request->input('old_password');
		$password		= $request->input('password');

		if (!$account || !$oldPassword || !$password) {
			return response()->json(Http::responseFail())->header('Access-Control-Allow-Origin', '*');
		}

		$type = preg_match('/^[0-9]+$/', $account) ? 'mobile' : 'username';
		$method = "findBy{$type}";
		$user = User::$method($account);

		if ($user->isEmpty()) {
			return response()->json(Http::responseFail('用户不存在', 412, 'auth_error'))->header('Access-Control-Allow-Origin', '*');
		} elseif ($user->password != String::md5Salt($oldPassword, $user->getId())) {
			return response()->json(Http::responseFail('旧密码错误'))->header('Access-Control-Allow-Origin', '*');
		} elseif (strlen($password) < 6 || strlen($password) > 30) {
			return response()->json(Http::responseFail('密码位数须在6~30之间'))->header('Access-Control-Allow-Origin', '*');
		}

		$info = $user->update(compact('password'));

		return $info ? response()->json(Http::responseDone())->header('Access-Control-Allow-Origin', '*') : response()->json(Http::responseFail())->header('Access-Control-Allow-Origin', '*');
	}
	
	/*
	 * 发送修改密码邮件或短信 get /users/verification
	 *
	 * 参数
	 *   mobile
	 *
	 * 返回值
	 *   {"meta": {"code": 200}}
	 */
	 public function sendVerification(Request $request)
	 {
		$mobile	= $request->input('mobile');

		if (!$mobile || !String::isMobile($mobile)) {
			return response()->json(Http::responseFail('帐号格式错误'));
		}

		$verification = rand(1000, 9999);
		$content = "验证码:{$verification}，请在30分钟内输入您的验证码哦，谢谢。【阿达游戏】";

		$flag = Http::sendMessage($mobile, $content);

		if ($flag) {
			PRedis::setex("verification:{$mobile}", 1800, $verification);
			return response()->json(Http::responseDone());
		} else {
			return response()->json(Http::responseFail('发送失败', 405, 'request_error'));
		}
	}
}
