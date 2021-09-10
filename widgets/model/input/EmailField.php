<?php
namespace mozzler\base\widgets\model\input;

use yii\helpers\ArrayHelper;
use yii\widgets\ActiveField;

class EmailField extends BaseField
{
	
	public function run() {
		$config = $this->config();
		/** @var ActiveField $field */
		$field = $config['form']->field($config['model'], $config['attribute'], $config['fieldOptions']);
		return $field->textInput(ArrayHelper::merge(['type' => 'email'], $config['widgetConfig']));
	}
	
}

