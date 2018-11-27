<?php
namespace mozzler\base\fields;

class IntegerInput extends Base {
	
	public $type = 'Integer';
	
	public function defaultRules() {
		return [
			'integer' => []
		];
	}
	
	// force integer value
	public function setValue($value) {
		if (isset($value))
			return intval($value);
	}
	
}

?>
