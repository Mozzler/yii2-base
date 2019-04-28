<?php
namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;

class CurrencyField extends BaseField {
    public function defaultConfig()
    {
        return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
            "tag" => "pre",
            "currencySymbol" => "$", // @todo: Make this automatically locale specific
            "decimalPlaces" => 2, // How many decimals to show
            "decimalSeparator" => '.',
            "thousandsSeparator" => ',',
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
        return $config;
    }

}
