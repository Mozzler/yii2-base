<?php
namespace mozzler\base\fields;

class Date extends Base {
	
	public $type = 'Date';
	public $operator = '<>';
	
	/**
	 * Force a given time value to be midnight on the requested date in the
	 * application timezone.
	 */
	public function setValue($value) {
		$value = parent::setValue($value);
		$timezone = new \DateTimeZone(\Yii::$app->formatter->timeZone);
		
		// Create a datetime object for the requested time
		$valueDatetime = new \DateTime();
		$valueDatetime->setTimestamp($value);
		
		// if we already have a timestamp, create a datetime object
		// and force midnight in the current application timezone
		$valueDatetime->setTimezone($timezone);
		
		// Force the time to be midnight on the requested date
		$datetime = new \DateTime();
		$date = $valueDatetime->format("Y-m-d")." 00:00:00";
		
		$midnight = $datetime->createFromFormat("Y-m-d H:i:s", $date, $timezone);
		
		// Return a unix epoch of the time at midnight
		return intval($midnight->format("U"));
	}
	
}

?>
