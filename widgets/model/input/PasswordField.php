<?php
namespace mozzler\base\widgets\model\input;

class PasswordField extends BaseField
{
	
	public function run() {
		$config = $this->config();
		$field = $config['form']->field($config['model'], $config['attribute']);
		return $field->passwordInput($config['widgetConfig']);
	}
	
}

?>