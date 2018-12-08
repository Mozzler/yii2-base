<?php
namespace mozzler\base\fields;

class SingleSelect extends Base {
	
	public $type = 'SingleSelect';
	public $operator = "~";
	public $options = [];
	
	public function defaultRules() {
		return [
			'string' => [
				'max' => 255
			]
		];
	}
	
}

?>
