<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', 'PageController@showPage');

Route::get('/{year}/{month}/{day}', 'PageController@showPage')
->where(array(
	'year' => '(19|20)\d\d',
	'month' => '\d\d',
	'day' => '\d\d'
));