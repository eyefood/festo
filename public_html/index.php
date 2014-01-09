<?php
require '../vendor/autoload.php';

use \Exception ;

require 'config.php' ;

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');
ob_start('mb_output_handler');

$app = new \Slim\Slim(array(
	'debug' => true,
    'view' => new \Slim\Views\Twig(),
    'templates.path' => '../lib/Festo/Views/'
));

$app->get('/', function () use ($app) {
	try {
		$day_controller = new Festo\Controllers\DayController(time()) ;

	} catch (Exception $e) {
		$directory = new \RecursiveDirectoryIterator( SOURCE_DIRECTORY );
		$iterator = new \RecursiveIteratorIterator($directory);
		$regex = new \RegexIterator($iterator, '/^.+\/([0-9]{4})\/([0-9]{2})\/([0-9]{2})\.md$/i', \RecursiveRegexIterator::GET_MATCH);
		$days = array() ;
		$timestamps = array() ;
		foreach($regex as $file)
		{
			$timestamps[] = strtotime($file[1] . '-' . $file[2] . '-' . $file[3]) ;
		}
		$day_controller = new Festo\Controllers\DayController(end($timestamps)) ;
	}
	return $app->render('day.html', array(
			'date' => $day_controller->date,
			'posts' => $day_controller->getPosts(),
			'previous_days' => $day_controller->getPreviousDays(10),
			'next_days' => $day_controller->getNextDays(10)
		)) ;

});

$app->get('/:year/:month/:day', function ($year, $month, $day) use ($app) {
	$date = strtotime($year . '-' . $month . '-' . $day) ;
	try {
		$day_controller = new Festo\Controllers\DayController($date) ;

	} catch (Exception $e) {
		$app->halt(404, 'Not Found');
	}

	return $app->render('day.html', array(
		'date' => $day_controller->getDate(),
		'posts' => $day_controller->getPosts(),
		'previous_days' => $day_controller->getPreviousDays(10),
		'next_days' => $day_controller->getNextDays(10)
	)) ;
});

$app->run();