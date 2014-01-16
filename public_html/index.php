<?php
require '../vendor/autoload.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Token');

use \Exception ;

require '../config.php' ;
session_start();

try
{
	$db = new PDO("mysql:host=localhost;dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
} catch (PDOException $e) {
	header("HTTP/1.0 503 Service Unavailable", true, 503);
	exit('Service Unavailable');
}

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
$app->add(new \Slim\Middleware\ContentTypes());

function auth(\Slim\Route $route) {
	global $db ;

    $app = \Slim\Slim::getInstance();
    $cookie = $app->getCookie('auth');
    $user = false;

    if(!empty($cookie))
    {
    	// check twitter credientials
    	$cookie_array = explode('-', $cookie);
    	$user_id = $cookie_array[0] ;
		$token = $cookie_array[1] ;

		if(!empty($user_id) && !empty($token))
		{
			$sql = "SELECT *
					FROM tokens, users
					WHERE tokens.user_id = :user_id
					AND tokens.token = :token
					AND tokens.user_id = users.oauth_uid
					" ;
			$sth = $db->prepare($sql) ;
			$sth->bindValue(':user_id', (int)$user_id, PDO::PARAM_INT) ;
			$sth->bindValue(':token', (int)$token, PDO::PARAM_INT) ;
			$sth->execute() ;
			while($row = $sth->fetch())
			{
				$user = array(
					'id' => $row['oauth_uid'],
			    	'name' => $row['full_name'],
			    	'avatar' => $row['avatar'],
			    	'screen_name' => $row['username']
			    );
			}
		}
    }

    $app->config('user', $user);
}

$app->get('/', 'auth', function () use ($app) {
	$user = $app->config('user');
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
	$next_days = $day_controller->getNextDays(10) ;
	$next_day = (empty($next_days)) ? false : $next_days[0] ;
	$previous_days = $day_controller->getPreviousDays(10) ;
	$previous_day = (empty($previous_days)) ? false : $previous_days[0] ;
	return $app->render('day.html', array(
		'date' => $day_controller->getDate(),
		'posts' => $day_controller->getPosts(),
		'previous_days' => $previous_days,
		'previous_day' => $previous_day,
		'next_days' => $next_days,
		'next_day' => $next_day,
		'user' => $user
	)) ;

});

$app->post('/', 'auth', function () use ($app) {
	$user = $app->config('user');
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

	$day_controller->addComment($user, $app->request->post('comment'), $app->request->post('duplicate_id') );
	$url = date("/Y/m/d", $day_controller->getDate()) ;

	$app->redirect($url);

});

$app->get('/logout', 'auth', function () use ($app) {
	global $db ;

	$cookie = $app->getCookie('auth');
    $cookie_array = explode('-', $cookie);
	$user_id = $cookie_array[0] ;
	$token = $cookie_array[1] ;

	$sql = "DELETE FROM tokens
			WHERE tokens.user_id = :user_id
			AND tokens.token = :token
			" ;
	$sth = $db->prepare($sql) ;
	$sth->bindValue(':user_id', (int)$user_id, PDO::PARAM_INT) ;
	$sth->bindValue(':token', (int)$token, PDO::PARAM_INT) ;
	$sth->execute() ;

	$_SESSION = array();
	session_destroy();

	$app->redirect('/');
});

$app->get('/:year/:month/:day', 'auth', function ($year, $month, $day) use ($app) {
	$user = $app->config('user');
	$date = strtotime($year . '-' . $month . '-' . $day) ;
	try {
		$day_controller = new Festo\Controllers\DayController($date) ;
	} catch (Exception $e) {
		$app->halt(404, 'Not Found');
	}
	$next_days = $day_controller->getNextDays(10) ;
	$next_day = (empty($next_days)) ? false : $next_days[0] ;
	$previous_days = $day_controller->getPreviousDays(10) ;
	$previous_day = (empty($previous_days)) ? false : $previous_days[0] ;
	return $app->render('day.html', array(
		'date' => $day_controller->getDate(),
		'posts' => $day_controller->getPosts(),
		'previous_days' => $previous_days,
		'previous_day' => $previous_day,
		'next_days' => $next_days,
		'next_day' => $next_day,
		'user' => $user
	)) ;
});

$app->post('/:year/:month/:day', 'auth', function ($year, $month, $day) use ($app) {
	$user = $app->config('user');
	$date = strtotime($year . '-' . $month . '-' . $day) ;
	// try
	// {
		$day_controller = new Festo\Controllers\DayController($date) ;
		$day_controller->addComment($user, $app->request->post('comment'), $app->request->post('duplicate_id') );

	// } catch (Exception $e) {
	// 	$app->halt(404, 'Not Found');
	// }
	$url = date("/Y/m/d", $date) . "#end" ;
	$app->redirect($url);
});

$app->run();