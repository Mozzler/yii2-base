<?php
namespace mozzler\base\fields;

class Text extends Base {
	
	public $type = 'Text';
	public $filterType = "LIKE";
	
	public function defaultRules() {
		return [
			'string' => [
				'max' => 255
			]
		];
	}
	
}


