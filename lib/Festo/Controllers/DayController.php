<?php

namespace Festo\Controllers ;
use \Festo,
	\Exception
	;

class DayController extends Controller
{
	public		$date
				;

	protected	$_day
				;

	function __construct($date=null) {
		if(!$date)
		{
			$date = time() ;
		}
		$this->_day = new \Festo\Models\Day($date) ;
	}


	/**
	 * [description here]
	 *
	 * @return [type] [description]
	 */
	public function getDate() {
	    return $this->_day->getDate();
	}

	/**
	 * [Description]
	 *
	 * @param [type] $newdate [description]
	 */
	public function setDate($date) {
	    $this->day->setDate($date) ;

	    return $this;
	}

	public function getPosts() {
		return $this->_day->getPosts() ;
	}

	public function getPreviousDays($per_page=7) {
		return $this->_day->getPreviousDays($per_page) ;
	}

	public function getNextDays($per_page=7) {
		return $this->_day->getNextDays($per_page) ;
	}
}