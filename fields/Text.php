<?php
namespace mozzler\base\fields;

class Text extends Base {
	
	public $type = 'Text';
	public $operator = "~";
	
	public function defaultRules() {
		return [
			'string' => [
				'max' => 255
			]
		];
	}
	
}

?>
