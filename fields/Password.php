<?php
namespace mozzler\base\fields;

class Password extends Base {
	
	public $type = 'Password';
	public $widgets = [
		'input' => 'mozzler\base\widgets\model\input\PasswordField',
		'view' => 'mozzler\base\widgets\model\view\PasswordField'
	];
	
	public function defaultRules() {
		return [
			'string' => ['min' => 6, 'max' => 30]
		];
	}
	
}

?>
