<?php
namespace mozzler\base\fields;

class DateTime extends Base {
	
	public $type = 'DateTime';
	
	/**
	 * Parse a given time value, being timezone aware, into a unix
	 * epoch integer
	 */
	public function setValue($value) {
		if (!$value) {
			return null;
		}

		// handle a string value
		if (is_string($value)) {
			// if $value is a string containing an integer, return the integer
			if (strval(intval($value)) == $value)
				return intval($value);
			
			$timezone = new \DateTimeZone(\Yii::$app->formatter->timeZone);
			$epoch = (new \DateTime($value, $timezone))->format("U");
			return intval($epoch);
		}
		
		return parent::setValue($value);
	}
	
}

?>
