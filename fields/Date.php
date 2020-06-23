<?php
namespace mozzler\base\fields;

class Date extends DateTime {
	
	public $type = 'Date';
	
	/**
	 * Force a given time value to be midnight on the requested date in UTC
	 */
	public function setValue($value) {
		$value = parent::setValue($value);

		if (!$value) {
			return null;
		}

		$timezone = new \DateTimeZone('UTC');
		
		// Create a datetime object for the requested time
		$valueDatetime = new \DateTime();
		$valueDatetime->setTimestamp($value);
		
		// if we already have a timestamp, create a datetime object
		// and force midnight in UTC
		$valueDatetime->setTimezone($timezone);
		
		// Force the time to be midnight on the requested date
		$datetime = new \DateTime();
		$date = $valueDatetime->format("Y-m-d")." 00:00:00";
		
		$midnight = $datetime->createFromFormat("Y-m-d H:i:s", $date, $timezone);
		
		// Return a unix epoch of the time at midnight
		return intval($midnight->format("U"));
	}
	
}
