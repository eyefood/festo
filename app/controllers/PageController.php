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
					
		$source = "" ;
		$path = Config::get('festo.source_dir') . "/{$year}/{$month}/{$day}.md" ;
		if(!file_exists($path))
		{
			$is_dir = true ;
			$path = Config::get('festo.source_dir') ;
			while($is_dir)
			{
				$files = scandir($path) ;
				$path = $path . "/" . end($files) ;
				$is_dir = is_dir($path) ? true : false ;
			}
		}
		$source = file_get_contents($path) ;
		$text = Markdown::string($source) ;
		return View::make('main', array('text' => $text) ) ;
	}
}