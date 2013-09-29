<?php
class PageController extends BaseController
{
	public function showPage($year=null, $month=null, $day=null)
	{
		if(!$year)
			$year = date('Y') ;
		if(!$month)
			$month = date('m') ;
		if(!$day)
			$day = date('d') ;
			
		$directory = new RecursiveDirectoryIterator( Config::get('festo.source_dir') );
		$iterator = new RecursiveIteratorIterator($directory);
		$regex = new RegexIterator($iterator, '/^.+\.md$/i', RecursiveRegexIterator::GET_MATCH);
		$files = array() ;
		foreach($regex as $file)
		{
			$files[] = $file[0] ;
		}
		$source = "" ;
		$path = Config::get('festo.source_dir') . "/{$year}/{$month}/{$day}.md" ;
		if(!file_exists($path))
		{
			$path = end($files) ;
		}
		$position = array_search($path, $files) ;
		$previous = array() ;
		for($i = $position-1; $i>$position - 10; $i--)
		{
			if(isset($files[$i]))
			{
				$file_date = substr(str_replace((Config::get('festo.source_dir') . '/'), '', $files[$i]), 0, -3) ;
				$previous[$file_date] = $files[$i] ;
			}
		}
		$next = array() ;
		for($i = $position+1; $i<$position + 10; $i++)
		{
			if(isset($files[$i]))
			{
				$file_date = substr(str_replace((Config::get('festo.source_dir') . '/'), '', $files[$i]), 0, -3) ;
				$next[$file_date] = $files[$i] ;
			}
		}
		$source = file_get_contents($path) ;
		$text = Markdown::string($source) ;
		return View::make('main', array('text' => $text, 'previous' => $previous, 'next' => $next) ) ;
	}
}