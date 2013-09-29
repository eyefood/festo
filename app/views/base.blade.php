<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
	<title></title>
	@section('head')
 	<link rel="stylesheet" href="/css/screen.css" />
	@show
</head>
<body>
	@section('header')
	<h1>Title</h1>
	@show
	
	@yield('body')

	@section('footer')
	@include('footer')
	@show
</body>
</html>