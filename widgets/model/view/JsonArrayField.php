<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;


class JsonArrayField extends BaseField
{
    public function defaultConfig()
    {
        return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
            "tag" => "pre",
            "options" => [
                "class" => "view-model-field-json-array",
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
            // Outputs as the compact Array format by default
            $config['prettyOutput'] = htmlentities(VarDumper::export($config['model']->$attribute));
            $config['options']['class'] .= " view-model-field-json-array--$attribute";
            $config['options']['class'] = trim($config['options']['class']);
        }
        return $config;
    }

}

?>