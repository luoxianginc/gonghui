@extends('common')

@section('content')
	<h1>修改密码</h1>

	<form action="{{ route('password') }}" method="post">
		<div class="form-group">
			<label for="account">帐号</label>
			<input type="text" class="form-control" id="account" placeholder="手机号/用户名" required>
		</div>

		<div class="form-group">
			<label for="old_password">旧密码</label>
			<input type="password" class="form-control" id="old_password" placeholder="旧密码" required>
			{{-- <p class="help-block">Example block-level help text here.</p> --}}
		</div>

		<div class="form-group">
			<label for="password">新密码</label>
			<input type="password" class="form-control" id="password" placeholder="新密码" required>
		</div>

		<button type="submit" class="btn btn-primary btn-lg">提交</button>
	</form>
@endsection
