<?php

namespace mozzler\base\widgets\model\input;

use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\widgets\ActiveField;

class IntegerField extends BaseField
{

    public function run()
    {
        $config = $this->config();
        /** @var ActiveField $field */
        $field = $config['form']->field($config['model'], $config['attribute']);

        // Setting the type to 'number' as you shouldn't be seeing a generic text field
        $widgetConfig = ArrayHelper::merge(['type' => 'number'], $config['widgetConfig']);
        return $field->textInput($widgetConfig);
    }

}

