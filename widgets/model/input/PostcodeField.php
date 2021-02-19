<?php

namespace mozzler\base\widgets\model\input;

use yii\helpers\ArrayHelper;
use yii\widgets\ActiveField;

class PostcodeField extends BaseField
{

    public function run()
    {
        $config = $this->config();
        /** @var ActiveField $field */
        $field = $config['form']->field($config['model'], $config['attribute']);

        return $field->textInput(ArrayHelper::merge(['type' => 'tel'], $config['widgetConfig'])); // Set as Telephone for the type
    }

}

