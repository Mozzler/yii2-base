<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;
use yii\helpers\ArrayHelper;

class CurrencyField extends BaseField
{
    public function defaultConfig()
    {
        return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
            "tag" => "span",
            "currencySymbol" => null, // @todo: Make this automatically locale specific
            "numberFormatterOptions" => [], // How many decimals to show example: [ \NumberFormatter::MIN_FRACTION_DIGITS => 0, \NumberFormatter::MAX_FRACTION_DIGITS => 2,
        ],
            "numberFormatterTextOptions" => [],
            "options" => [
                "class" => "",
            ],
            "model" => null,
            "attribute" => null
        ]);
    }

    public function config($templatify = true)
    {
        $config = parent::config(true);

        if (!empty($config['attribute']) && !empty($config['model'])) {
            $attributeValue = ArrayHelper::getValue($config, 'model.' . $config['attribute']);
            $config['formattedValue'] = \Yii::$app->formatter->asCurrency($attributeValue, $config['currencySymbol'], $config['numberFormatterOptions'], $config['numberFormatterTextOptions']);
        }
        return $config;
    }

}
