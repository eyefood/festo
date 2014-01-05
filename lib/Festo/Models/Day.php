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
			$posts = array()
			;


	public function __construct($date=null)
	{
		if(!$date)
		{
			$date = time() ;
		}
		$this->setDate($date) ;
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
		$count = 0 ;
		$filename = SOURCE_DIRECTORY . date('Y/m/d\.\m\d', $this->getDate()) ;
		if(!file_exists($filename)) {
			throw new \Exception(date('Y/m/d\.\m\d', $this->getDate()) . ' not found') ;
		}
		$html =  SmartyPants::defaultTransform(
			'<html><head></head><body>' . Markdown::defaultTransform(
				file_get_contents($filename)
			) . '</body></html>'
		);
		$dom = new DOMDocument() ;
		@$dom->loadHTML($html) ;
		foreach($dom->getElementsByTagName('h1') as $node) {
		    $this->titles[] = $node->textContent;
		}
		return $this->titles ;
	}

	public function getPosts() {
		$count = 0 ;
		$filename = SOURCE_DIRECTORY . date('Y/m/d\.\m\d', $this->getDate()) ;
		if(!file_exists($filename)) {
			throw new Exception(date('Y/m/d\.\m\d', $this->getDate()) . ' not found') ;
		}
		$raw_html =  SmartyPants::defaultTransform(
			Markdown::defaultTransform(
				file_get_contents($filename)
			)
		);
		$html = '<html><head></head><body>' . $raw_html . '</body></html>' ;
		$dom = new DOMDocument() ;
		@$dom->loadHTML($html) ;
		foreach($dom->getElementsByTagName('h1') as $node) {
			$post = new Post ;
		    $post->title = $node->textContent;
		    $post->body = '' ;
		    while(($node = $node->nextSibling) && $node->nodeName !== 'h1') {
		        $post->body .= $dom->saveHtml($node);
		    }
		    $this->posts[] = $post ;
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

		foreach(array_slice($timestamps, ($position + 1), $per_page, true) as $date)
		{
			$days[] = new Day($date) ;
		}
		return array_reverse($days) ;
	}
}