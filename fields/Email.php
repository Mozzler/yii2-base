<?php
namespace mozzler\base\fields;

class Email extends Base {
	
	public $type = 'Email';
	public $filterType = "LIKE";
	
	public function defaultRules() {
		return [
			'string' => ['min' => 6, 'max' => 100],
			'filter' => ['filter' => 'trim'],
			'email' => []
		];
	}
	
}
