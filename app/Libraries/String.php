<?php

namespace App\Libraries;

class String
{
	public static function md5Salt($meat, $salt)
	{
		return md5($meat . md5($salt));
	}

	public static function isEmail($email)
	{
		$reg = '/[_a-z0-9-]+(.[_a-z0-9-]+)*@[a-z0-9-]+(.[a-z0-9-]+)*$/';
		if (strstr($email, '@') && strstr($email, '.') && preg_match($reg, $email)) { 
			return true; 
		} 
		return false;
	}
	
	public static function isMobile($mobile)
	{
		$reg = '/^1[0-9]{10}$/';
		if (preg_match($reg, $mobile)) { 
			return true; 
		} 
		return false;
	}
}
