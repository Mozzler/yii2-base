<?php
namespace mozzler\base\fields;

class TextLarge extends Base {
	
	public $type = 'TextLarge';
	public $operator = "LIKE";
	
	public function defaultRules() {
		return [
			'string' => []
		];
	}
	
}

?>
