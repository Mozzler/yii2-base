<?php
namespace mozzler\base\widgets\model\input;

use yii\helpers\ArrayHelper;

class BooleanField extends BaseField
{

    public function defaultConfig()
	{
        return ArrayHelper::merge(parent::defaultConfig(), [
            'widgetConfig' => [
                'options' => [],
                'enclosedByLabel' => true
            ]
        ]);
    }

    public function run() {
		$config = $this->config();
		$field = $config['form']->field($config['model'], $config['attribute']);
		return $field->checkbox($config['widgetConfig']['options'], $config['widgetConfig']['enclosedByLabel']);
	}

}

