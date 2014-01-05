<?php

namespace Festo\Controllers ;
use \Festo ;
use \Exception ;

class PreviousPostsController extends Controller
{
	function __construct($date=null) {
		if(!$date)
		{
			$date = time() ;
		}
		$this->setDate($date) ;
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
}

