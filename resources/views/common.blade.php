<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
	<meta name="format-detection" content="telephone=no" />

	<meta name="title" content="{{ $title or '工会' }}" />
	<meta name="keywords" content="{{ $title or '工会' }}" />
	<meta name="description" content="{{ $description or '工会' }}" />

	<title>{{ $title or '工会' }}</title>

	<link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
	<link rel="stylesheet" type="text/css" href="{{ asset("css/{$css}.css") }}">
	<script type="text/javascript" src="{{ asset("js/{$js}.js") }}"></script>
</head>

<body>
	@include('header')

	<div class="container-fluid" id="main">
		@yield('content')
	</div>

	@include('footer')
</body>
</html>
