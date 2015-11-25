<?php

namespace App\Models;

use PRedis;
use App\Libraries\String;

class Game
{
	private $id;
	private $info;

	/**
     * 构造方法 
     */
	public function __construct($gameId)
	{
		$this->id = $gameId;
		$this->info = PRedis::hGetAll("game:{$gameId}:info");
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
     * 批量修改游戏信息
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

		PRedis::hMSet("game:{$this->id}:info", $info);
		$this->info = PRedis::hGetAll("game:{$this->id}:info");

		return array_add($this->info, 'id', $this->id);
	}

	/**
     * 获取游戏信息
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
	 public function getId()
	 {
		return $this->id;
	 }

	/**
     * 判断对象是否为空
	 *
     * @return boolean
     */
	 public function isEmpty()
	 {
		return empty($this->id);
	 }
}
