<?php
namespace mozzler\base\fields;

class Timestamp extends Integer {
	
	public $type = 'Timestamp';

	public function setValue($value) {
		if (!$value) {
			return null;
		}
	}
	
}

?>