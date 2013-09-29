@extends('base')

@section('body')
	<article>
		{{ $text }}
	</article>
@stop

@section('previous')
	<ol>
	@foreach($previous as $date => $file_name)
		<li><a href="/{{ $date }}">{{ $date }}</a></li>
	@endforeach
	</ol>
@stop

@section('next')
	<ol>
	@foreach($next as $date => $file_name)
		<li><a href="/{{ $date }}">{{ $date }}</a></li>
	@endforeach
	</ol>
@stop