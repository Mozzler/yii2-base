<?php
namespace mozzler\libraries\fields;

class TextLarge extends Base {
	
	public $type = 'TextLarge';
	public $operator = "~";
	
	public function defaultRules() {
		return [
			'string' => []
		];
	}
	
}

?>
