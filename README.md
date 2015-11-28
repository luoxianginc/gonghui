#用户

##登录 post /user/access_token

###参数
|参数|描述|
|----|----|
|mobile|手机号|
|verification|验证码|
|timestamp|时间戳|
|nonce|序列码|
|signature|签名|

|参数|描述|
|----|----|
|username/mobile|用户名/手机号|
|password|密码|
|timestamp|时间戳|
|nonce|序列码|
|signature|签名|

|参数|描述|
|----|----|
|access_token|认证令牌|
|password|密码|
|timestamp|时间戳|
|nonce|序列码|
|signature|签名|

###返回值
```
{
	"meta": {"code": 200},
	"data": {
		"sdk_access_token": "8576b5acba85fa399bd77d37b828ed9f",
		"access_token": "99f3322d8c2c31362529c31436fee1b7",
		"expires_in": 1800
	}
}
```
---

##注册 post /user

###参数
|参数|描述|
|----|----|
|username/mobile|用户名/手机号|
|password|密码|
|timestamp|时间戳|
|nonce|序列码|
|signature|签名|

###返回值
```
{
	"meta": {"code": 200},
	"data": {
		"sdk_access_token": "8576b5acba85fa399bd77d37b828ed9f",
		"access_token": "99f3322d8c2c31362529c31436fee1b7"
		"expires_in": 1800
	}
}
```

---

##修改用户密码网页 get /user/password

---

##修改用户密码 post /user/password

###参数
|参数|描述|
|----|----|
|account|帐号|
|old_password|旧密码|
|password|密码|

###返回值
```
{"meta": {"code": 200}}
```

---

##发送修改密码邮件或短信 get /user/verification

###参数
|参数|描述|
|----|----|
|mobile|手机号|

###返回值
```
{"meta": {"code": 200}}
```

---
#游戏

##获取游戏信息 get /game/{gameId}

###返回值
```
{
	"meta": {"code": 200},
	"data": { ... }
}
```
---

