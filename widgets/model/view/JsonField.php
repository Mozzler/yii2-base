<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

class JsonField extends BaseField
{
    public function defaultConfig()
    {
        return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
            "tag" => "pre",
            "options" => [
                "class" => "view-model-field-json",
            ],
            "model" => null,
            "attribute" => null
        ]);
    }

    public function config($templatify = false)
    {
        $config = parent::config();
        if (!empty($config['attribute']) && !empty($config['model'])) {
            $attribute = $config['attribute'];
            $value = $config['model']->$attribute;

            //-- If it's a string try to JSON decode it
            if (is_string($value)) {
                try {
                    $value = \yii\helpers\Json::decode($value);
                } catch (\Throwable $exception) {
                    // -- Example as per https://github.com/yiisoft/yii2/issues/11266
                    $value = "Invalid JSON, unable to decode";
                    \Yii::error("The attribute $attribute has invalid Json: " . VarDumper::export([
                            'JSON' => $value,
                            'Exception' => \Yii::$app->t::returnExceptionAsString($exception),
                        ]));
                }
            }


            if (!is_string($value)) {
                // Create a nice JSON output with spacing and line breaks, but encode the HTML and don't show the JSON escape slashes which makes URLs hard to read
                // NB: The newline replacement causes issues with parsing the JSON in something like the browser console for multi-line entries
                $config['prettyJson'] = htmlentities(str_replace('\n', '
  ', json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)));
            }
            $config['options']['class'] .= " view-model-field-json--$attribute";
            $config['options']['class'] = trim($config['options']['class']);
        }
        return $config;
    }

}

