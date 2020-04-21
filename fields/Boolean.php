<?php
namespace mozzler\base\fields;

class Boolean extends Base {
	
	public $type = 'Boolean';
	
	public function defaultRules() {
		return [
			'boolean' => []
		];
	}
	
	// force integer value
	public function setValue($value) {
		if (isset($value) && $value)
			return true;
		
		return false;
	}
	
}
