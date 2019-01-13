<?php
namespace mozzler\base\fields;

class Text extends Base {
	
	public $type = 'Text';
	public $operator = "LIKE";
	
	public function defaultRules() {
		return [
			'string' => [
				'max' => 255
			]
		];
	}
	
}

?>
