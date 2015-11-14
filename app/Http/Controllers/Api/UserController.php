<?php

namespace App\Http\Controllers\Api;

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
	 * 注册 post /users
	 *
	 * 参数
	 *   email/mobile/qq_id/wechat_id/weibo_id
	 *   password
	 *   timestamp
	 *   nonce
	 *   signature
	 *
	 * 返回值
	 *   email/mobile:
	 *   {
	 *       "meta": {"code": 200},
	 *	     "data": {
	 *		     "id"			: "8190605548",
	 *		     "access_token"	: "99f3322d8c2c31362529c31436fee1b7",
	 *		     "name"			: "手机用户15672493" // 针对mobile 
	 *	     }
	 *	 }
	 *
	 *   第三方:
	 *
	 *   {
	 *		 "meta": {"code": 200},
	 *		 "data":{
	 *			 "id"			: "8190605548",
	 *			 "access_token"	: "99f3322d8c2c31362529c31436fee1b7",
	 *			 "qq_id"		: "...",
	 *			 "name"			: "张三",
	 *			 "avatar"		: "...",
	 *			 "gender"		: "male",
	 *			 "location"		: "福建"
	 *	     }
	 *	 }
	 */
	public function create(Request $request) 
	{
		$email		= strtolower($request->input('email'));
		$mobile		= $request->input('mobile');
		$qq_id		= $request->input('qq_id');
		$wechat_id	= $request->input('wechat_id');
		$weibo_id	= $request->input('weibo_id');

		$password	= $request->input('password');
		$timestamp	= $request->input('timestamp');
		$nonce		= $request->input('nonce');
		$signature	= $request->input('signature');

		$type = collect(compact('email', 'mobile', 'qq_id', 'wechat_id', 'weibo_id'))->filter(function($item){
			return !empty($item);
		})->keys()->first();

		if (!$type || !$timestamp || !$nonce || !$signature) {
			return response()->json(Http::responseFail());
		} elseif (
			($type == 'email' && !String::isEmail($email)) ||
			($type == 'mobile' && !String::isMobile($mobile))
		) {
			return response()->json(Http::responseFail('帐号格式错误'));
		} elseif (
			($type == 'email' || $type == 'mobile') &&
			(!$password || strlen($password) < 6 || strlen($password) > 30)
		) {
			return response()->json(Http::responseFail('密码位数须在6~30之间'));
		} elseif (
			($type == 'email' && PRedis::hExists('emails', $email)) ||
			($type == 'mobile' && PRedis::hExists('mobiles', $mobile))
		) {
			return response()->json(Http::responseFail('账号已被注册', 412, 'param_error'));
		}

		if ($type == 'email' || $type == 'mobile') {
			$sign = Http::signature('users', compact($type, 'password', 'timestamp', 'nonce'));
			$info = ['name' => '', 'password' => $password];

			if ($type == 'mobile') {
				$info['name'] = '手机用户' . rand(10000000, 99999999);
			}
		} else {
			$params = compact('timestamp', 'nonce');
			$params[$type] = $$type;

			if ($request->has('name')) {
				$params['name'] = $request->input('name');
			}

			if ($request->has('avatar')) {
				$params['avatar'] = $request->input('avatar');
			}

			if ($request->has('gender')) {
				$params['gender'] = $request->input('gender');
			}

			if ($request->has('location')) {
				$params['location'] = $request->input('location');
			}

			$sign = Http::signature('users', $params);
			$info = array_except($params, ['timestamp', 'nonce']);
		}

		if ($sign != $signature) {
			return response()->json(Http::responseFail('非法请求', 405, 'request_error'));
		}

		if ($type != 'email' && $type != 'mobile' && PRedis::hExists("{$type}s", $$type)) {
			$userId = PRedis::hGet("{$type}s", $$type);
			$data = collect(PRedis::hMGet("user:{$userId}:info", ['name', 'avatar', 'gender', 'location', 'access_token']))->filter(function($item){
				return !empty($item);
			})->toArray();
			$data['id'] = $userId;
		} else {
			$createdIp = $request->server('REMOTE_ADDR');
			$data['access_token'] = User::register($type, $$type, $createdIp, $info);
			$data['id'] = PRedis::hGet('access_tokens', $data['access_token']);

			if ($type != 'email' && $type != 'mobile') {
				$data = array_merge($data, $info);
			} elseif ($type == 'mobile') {
				$data['name'] = $info['name'];
			}
		}

		return response()->json(Http::responseDone($data));
	}

	/*
	 * 更新用户信息 post /users/me
	 *
	 * 参数
	 *   access_token/mobile
	 *   name		可选
	 *   password	可选
	 *   gender		可选
	 *   location	可选
	 *   avatar		可选
	 *
	 * 返回值
	 *   {
	 *		 "meta": {"code": 200},
	 *		 "data":{
	 *			 "id"			: "8190605548",
	 *			 "access_token"	: "99f3322d8c2c31362529c31436fee1b7",
	 *			 "email"		: "...",
	 *			 "name"			: "张三",
	 *			 "avatar"		: "...",
	 *			 "gender"		: "male",
	 *			 "location"		: "福建"
	 *	     }
	 *	 }
	 */
	public function update(Request $request) 
	{
		$accessToken	= $request->input('access_token');
		$mobile			= $request->input('mobile');
		$name			= $request->input('name');
		$password		= $request->input('password');
		$gender			= $request->input('gender');
		$location		= $request->input('location');
		$avatar			= $request->input('avatar');
		$user			= $accessToken ? new User($accessToken) : User::findByMobile($mobile);

		if ($user->isEmpty()) {
			return response()->json(Http::responseFail('用户不存在', 412, 'auth_error'));
		}

		if (($name && (strlen($name) < 3 || strlen($name) > 48)) || ($gender && !in_array($gender, ['male', 'female']))) {
			return response()->json(Http::responseFail());
		}

		$info = $user->update(compact('name', 'password', 'gender', 'location', 'avatar'));

		return $info ? response()->json(Http::responseDone($info)) : response()->json(Http::responseFail());
	}
	
	/*
	 * 获取用户信息 get /users/me /users/{userId}
	 *
	 * 参数
	 *   access_token/mobile
	 *   name		可选
	 *   password	可选
	 *   gender		可选
	 *   location	可选
	 *   avatar		可选
	 *
	 * 返回值
	 *   {
	 *		 "meta": {"code": 200},
	 *		 "data":{
	 *			 "id"			: "8190605548",
	 *			 "access_token"	: "99f3322d8c2c31362529c31436fee1b7", // 针对/users/me
	 *			 "email"		: "...",
	 *			 "name"			: "张三",
	 *			 "avatar"		: "...",
	 *			 "gender"		: "male",
	 *			 "location"		: "福建"
	 *	     }
	 *	 }
	 */
	public function get(Request $request) 
	{
		$accessToken	= $request->get('access_token');
		$userId			= $request->route('userId');
		$user			= $userId ? User::find($userId) : new User($accessToken);

		if ((!$accessToken && !$userId) || $user->isEmpty()) {
			return response()->json(Http::responseFail());
		}

		$data = array_only($user->all(), [
			'id',
			'access_token',
			'email',
			'name',
			'gender',
			'location',
			'avatar'
		]);

		if ($userId) {
			unset($data['access_token']);
		}

		return response()->json(Http::responseDone($data));
	}
	
	/*
	 * 登录 post /users/me/access_token
	 *
	 * 参数
	 *   email/mobile
	 *   password
	 *   timestamp
	 *   nonce
	 *   signature
	 *
	 * 返回值
	 *   {
	 *		 "meta": {"code": 200},
	 *		 "data": "99f3322d8c2c31362529c31436fee1b7"
	 *	 }
	 */
	public function getAccessToken(Request $request) 
	{
		$email = $request->get('email');
		$mobile = $request->get('mobile');
		$password = $request->get('password');
		$timestamp = $request->get('timestamp');
		$nonce = $request->get('nonce');
		$signature = $request->get('signature');

		$type = $email ? 'email' : ($mobile ? 'mobile' : '');

		if (!$type || !$password || !$timestamp || !$nonce || !$signature) {
			return response()->json(Http::responseFail());
		}

		$sign = Http::signature('users/me/access_token', compact($type, 'password', 'timestamp', 'nonce'));
		if ($sign != $signature) {
			return response()->json(Http::responseFail('非法请求', 405, 'request_error'));
		}

		$res = User::login($$type, $password, $type);
		
		if (array_get($res, 'meta.code') == 400) {
			return response()->json($res);
		}

		$accessToken = array_get($res, 'data.access_token');

		return response()->json(Http::responseDone($accessToken));
	}
}
