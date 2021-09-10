<?php

namespace mozzler\base\widgets\model\input;

use yii\helpers\ArrayHelper;
use yii\widgets\ActiveField;

class PhoneField extends BaseField
{

    public function run()
    {
        $config = $this->config();
        /** @var ActiveField $field */
        $field = $config['form']->field($config['model'], $config['attribute'], $config['fieldOptions']);

        if (empty($config['widgetConfig']['hint']) && empty($config['widgetConfig']['placeholder'])) {
            $config['widgetConfig']['placeholder'] = '0412123123'; // Add a default placeholder
        }

        return $field->textInput(ArrayHelper::merge(['type' => 'tel'], $config['widgetConfig']));
    }

}

