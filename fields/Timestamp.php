<?php
namespace mozzler\base\fields;

class Timestamp extends DateTime {
	
	public $type = 'Timestamp';

	public function setValue($value) {
		if (!$value) {
			return null;
		}

		return $value;
	}
	
}

