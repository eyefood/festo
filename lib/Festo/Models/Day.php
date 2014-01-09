<?php

namespace Festo\Models ;
use \DOMDocument,
	\DOMXPath,
	\Michelf\Markdown,
	\Michelf\SmartyPants
	;

class Day
{
	public	$date,
			$titles,
			$posts = array(),
			$comments = array(),
			$raw_text
			;


	public function __construct($date=null)
	{
		if(!$date)
		{
			$date = time() ;
		}
		$this->setDate($date) ;

		$filename = SOURCE_DIRECTORY . date('Y/m/d\.\m\d', $this->getDate()) ;
		if(!file_exists($filename)) {
			$directory = new \RecursiveDirectoryIterator( SOURCE_DIRECTORY );
			$iterator = new \RecursiveIteratorIterator($directory);
			$regex = new \RegexIterator($iterator, '/^.+\/([0-9]{4})\/([0-9]{2})\/([0-9]{2})\.md$/i', \RecursiveRegexIterator::GET_MATCH);
			$days = array() ;
			$timestamps = array() ;
			foreach($regex as $file)
			{
				$timestamps[] = strtotime($file[1] . '-' . $file[2] . '-' . $file[3]) ;
			}
			$this->setDate(end($timestamps)) ;
		}
		$this->raw_text = file_get_contents($filename) ;
		$this->getTitles() ;
	}

	/**
	 * [description here]
	 *
	 * @return [type] [description]
	 */
	public function getDate() {
	    return $this->date;
	}

	/**
	 * [Description]
	 *
	 * @param [type] $newdate [description]
	 */
	public function setDate($date) {
	    $this->date = $date;

	    return $this;
	}

	public function getTitles() {
		$html =  SmartyPants::defaultTransform(
			'<html><head></head><body>' . Markdown::defaultTransform(
				mb_convert_encoding($this->raw_text, 'HTML-ENTITIES', "UTF-8")
			) . '</body></html>'
		);
		$dom = new DOMDocument() ;
		@$dom->loadHTML($html) ;
		$i = 1 ;
		foreach($dom->getElementsByTagName('h1') as $node) {
			$text = $node->textContent ;
			$length = 40 ;
			if(strlen($text) > $length) {
		      $text = preg_replace("/^(.{1,$length})(\s.*|$)/s", '\\1...', $text);
		   }
		    $this->titles[] = array(
		    	'text' => $text,
		    	'slug' => $i . "-" . strtolower(str_replace(' ', '-', trim(preg_replace("/[^a-zA-Z0-9 ]/", "", strip_tags($node->textContent)))))
		    );
		    $i++ ;
		}
		return $this->titles ;
	}

	public function getPosts() {
		$this->extractComments() ;
		$raw_html =  SmartyPants::defaultTransform(
			Markdown::defaultTransform(
				mb_convert_encoding($this->raw_text, 'HTML-ENTITIES', "UTF-8")
			)
		);
		$html = '<html><head></head><body>' . $raw_html . '</body></html>' ;
		$dom = new DOMDocument() ;
		@$dom->loadHTML($html) ;
		$i = 1 ;
		foreach($dom->getElementsByTagName('h1') as $node) {
			$post = new Post ;
		    $post->title = $node->textContent;
		    $post->body = '' ;
		    while(($node = $node->nextSibling) && $node->nodeName !== 'h1') {
		        $post->body .= $dom->saveHtml($node);
		    }
		    $post->slug = $i . "-" . strtolower(str_replace(' ', '-', trim(preg_replace("/[^a-zA-Z0-9 ]/", "", strip_tags($post->title))))) ;
		    $this->posts[] = $post ;
		    $i++ ;
		}
		if(empty($this->posts))
		{
			$post = new Post ;
			$post->title = 'No title' ;
			$post->body = $raw_html ;
			$this->posts[] = $post ;
		}
		return $this->posts ;
	}

	public function getPreviousDays($per_page=7)
	{
		$directory = new \RecursiveDirectoryIterator( SOURCE_DIRECTORY );
		$iterator = new \RecursiveIteratorIterator($directory);
		$regex = new \RegexIterator($iterator, '/^.+\/([0-9]{4})\/([0-9]{2})\/([0-9]{2})\.md$/i', \RecursiveRegexIterator::GET_MATCH);
		$days = array() ;
		$timestamps = array() ;
		foreach($regex as $file)
		{
			$timestamps[] = strtotime($file[1] . '-' . $file[2] . '-' . $file[3]) ;
		}

		$position = array_search($this->getDate(), $timestamps) ;
		foreach(array_slice($timestamps, ($position - $per_page), $per_page, true) as $date)
		{
			$days[] = new Day($date) ;
		}

		return array_reverse($days) ;
	}

	public function getNextDays($per_page=7)
	{
		$directory = new \RecursiveDirectoryIterator( SOURCE_DIRECTORY );
		$iterator = new \RecursiveIteratorIterator($directory);
		$regex = new \RegexIterator($iterator, '/^.+\/([0-9]{4})\/([0-9]{2})\/([0-9]{2})\.md$/i', \RecursiveRegexIterator::GET_MATCH);
		$days = array() ;
		$timestamps = array() ;
		foreach($regex as $file)
		{
			$timestamps[] = strtotime($file[1] . '-' . $file[2] . '-' . $file[3]) ;
		}

		$position = array_search($this->getDate(), $timestamps) ;
		if($position)
		{
			foreach(array_slice($timestamps, ($position + 1), $per_page, true) as $date)
			{
				$days[] = new Day($date) ;
			}
		}
		return $days ;
	}

	public function extractComments()
	{
		preg_match_all ("/<!-- -----BEGIN COMMENT v2.0----- -->([^<][^!]*)<!-- ------END COMMENT v2.0------ -->/s" , $this->raw_text, $comment_source) ;
		$lr = "right" ;
		$prev_commenter = "foo" ;
		for ($i=0; $i < count($comment_source[0]); $i++)
		{
			$comment = explode("|", $comment_source[1][$i]) ;
			$this->comments[] = $comment ;
			if ($comment[1] !="")
			{
				if (substr($comment[1], 0, 7) != "http://")
				{
					$comment[1] = "http://" . $comment[1] ;
				}
				$comment_url = '<a href="' . $comment[1] . '">' ;
				$comment_url_close = "</a>" ;
			} else {
				$comment_url = "" ;
				$comment_url_close = "" ;
			}
			$grav_url = "http://www.gravatar.com/avatar.php?gravatar_id=" . md5($comment[2]) . "&amp;default=".urlencode('http://cagd.co.uk/avatar/default.jpg') ;
			if (($lr == "left") && (md5($comment[0]) != $prev_commenter))
			{
				$lr = "right" ;
			} elseif (($lr == "right") && (md5($comment[0]) != $prev_commenter)) {
			 	$lr = "left" ;
			}
			$prev_commenter = md5($comment[0]) ;

			$para_pattern = "/^<\/p>/" ;
			$comment_body = Markdown::defaultTransform(preg_replace($para_pattern, "", $comment[3])) ;
			$comment_body = str_replace( "<p><blockquote>", "<blockquote>\n  <p>", $comment_body) ;
			$comment_body = preg_replace( "/<p>[\s]*<p>/s", "<p>" , $comment_body) ;
			$comment_body = preg_replace( "/<\/p>[\s]*<\/p>/s", "</p>" , $comment_body) ;
			$comment_body = preg_replace( "/<p><p>/s", "<p>" , $comment_body) ;
			$comment_body = preg_replace( "/<p><\/p>/", "" , $comment_body) ;
			$comment_body = preg_replace( "/<\/p><\/p>/s", "</p>" , $comment_body) ;
			$comment_body = str_replace( "</blockquote></p>", "</p>\n</blockquote>", $comment_body) ;

			if(!isset($comment[4])) { $comment[4] = "old-code" ; }

			$pattern = $comment_source[0][$i] ;
			$replace = "\n\n<a name=\"" . substr($comment[4], 0, -1)."\"></a>
							<div class=\"comment-v2-" . $lr . "\">
								<img class=\"avatar\" src=\"" . $grav_url . "\" />
								<span> <p class=\"name\">" . $comment_url . $comment[0] . $comment_url_close . "</p>" . $comment_body . "</span>
							</div>\n\n" ;
			$this->raw_text = str_replace($pattern, $replace , $this->raw_text) ;
		}
	}
}