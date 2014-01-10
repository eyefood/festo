<?php
require '../vendor/autoload.php';
require '../config.php' ;

try
{
	$db = new PDO("mysql:host=localhost;dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
} catch (PDOException $e) {
	header("HTTP/1.0 503 Service Unavailable", true, 503);
	exit('Service Unavailable');
}

session_start();

if(!empty($_GET['oauth_verifier']) && !empty($_SESSION['oauth_token']) && !empty($_SESSION['oauth_token_secret']))
{
    $twoauth = new TwitterOAuth(TWITTER_OAUTH_CONSUMER_KEY, TWITTER_OAUTH_CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
    $access_token = $twoauth->getAccessToken($_GET['oauth_verifier']);
    $_SESSION['access_token'] = $access_token;
    $user_info = $twoauth->get('account/verify_credentials');

    if(!isset($user_info->id) || empty($user_info->id))
    {
        print_r($user_info) ;
        die('Twitter says no') ;
        $url = "/" ;
        header("Location: " . $url);
    }

    $sql = "SELECT COUNT(*) AS total
    		FROM users
    		WHERE oauth_uid = :oauth_uid
    		" ;
    $sth = $db->prepare($sql) ;
    $sth->bindValue(':oauth_uid', $user_info->id, PDO::PARAM_STR) ;
    $sth->execute() ;
    if($sth->fetchColumn() > 0)
    {
    	// update
    	$sql = "UPDATE users
    			SET oauth_provider = :oauth_provider,
    				oauth_uid = :oauth_uid,
    				oauth_token = :oauth_token,
    				oauth_secret = :oauth_secret,
                    username = :username
                    avatar = :avatar
                    full_name = :full_name
    			WHERE oauth_uid = :oauth_uid
    			";
    	$sth = $db->prepare($sql) ;
	    $sth->bindValue(':oauth_uid', $user_info->id, PDO::PARAM_STR) ;
    	$sth->bindValue(':oauth_provider', 'twitter', PDO::PARAM_STR) ;
    	$sth->bindValue(':oauth_uid', $user_info->id, PDO::PARAM_STR) ;
    	$sth->bindValue(':oauth_token', $access_token['oauth_token'], PDO::PARAM_STR) ;
    	$sth->bindValue(':oauth_secret', $access_token['oauth_token_secret'], PDO::PARAM_STR) ;
    	$sth->bindValue(':username', $user_info->screen_name, PDO::PARAM_STR) ;
        $sth->bindValue(':avatar', $user_info->profile_image_url, PDO::PARAM_STR) ;
        $sth->bindValue(':full_name', $user_info->name, PDO::PARAM_STR) ;

    	$sth->execute() ;

    } else {
    	// insert
    	$sql = "INSERT INTO users (
    				oauth_provider, oauth_uid, oauth_token, oauth_secret, username, avatar, full_name
    			) VALUES (
    				:oauth_provider, :oauth_uid, :oauth_token, :oauth_secret, :username, :avatar, :full_name
    			)";
    	$sth = $db->prepare($sql) ;
    	$sth->bindValue(':oauth_provider', 'twitter', PDO::PARAM_STR) ;
        $sth->bindValue(':oauth_uid', $user_info->id, PDO::PARAM_STR) ;
    	$sth->bindValue(':oauth_token', $access_token['oauth_token'], PDO::PARAM_STR) ;
    	$sth->bindValue(':oauth_secret', $access_token['oauth_token_secret'], PDO::PARAM_STR) ;
        $sth->bindValue(':username', $user_info->screen_name, PDO::PARAM_STR) ;
        $sth->bindValue(':avatar', $user_info->profile_image_url, PDO::PARAM_STR) ;
        $sth->bindValue(':full_name', $user_info->name, PDO::PARAM_STR) ;

    	$sth->execute() ;
    }

    $expire = time()+60*60*24*365 ;
	$token = rand(0,9223372036854775807) ;
	setcookie("auth", "{$user_info->id}-{$token}", $expire, '/') ;
	$_COOKIE['auth'] = "{$user_info->id}-{$token}" ;
	$sql = "INSERT INTO tokens (
				user_id, token
			) VALUES (
				:user_id, :token
			)" ;
	$sth = $db->prepare($sql) ;
	$sth->bindValue(':user_id', (int)$user_info->id, PDO::PARAM_INT) ;
	$sth->bindValue(':token', (int)$token, PDO::PARAM_INT) ;
	$sth->execute() ;


    $url = "/" ;
    header("Location: " . $url);

} else {

    $twoauth = new TwitterOAuth(TWITTER_OAUTH_CONSUMER_KEY,TWITTER_OAUTH_CONSUMER_SECRET);
    $request_token = $twoauth->getRequestToken('http://festo.eyefood.co.uk/twitter_auth.php');
    $_SESSION['oauth_token'] = $request_token['oauth_token'];
    $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
    if ($twoauth->http_code == 200)
    {
        $url = $twoauth->getAuthorizeURL($request_token['oauth_token']);
        header("Location: " . $url);
    } else {
        die("oops... something's wrong!");
    }
}