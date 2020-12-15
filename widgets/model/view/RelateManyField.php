<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;
use yii\helpers\VarDumper;

class RelateManyField extends BaseField
{

    public function defaultConfig()
    {
        return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
            "tag" => "a",
            "options" => [
                "class" => "",
                "href" => "test" // {{ widget.model.getRelated(widget.attribute).getUrl('view') }}
            ],
            "label" => "label", // {{ widget.model.getRelated(widget.attribute).name }}
            "model" => null,
            "attribute" => null
        ]);
    }

    public function config($templatify = true)
    {
        $config = parent::config(true);
        \Yii::debug("The relateManyField view: " . VarDumper::export($config));

        $modelField = $config['model']->getModelField($config['attribute']);
        $value = $config['model'][$config['attribute']];

        // Likely an array of entries
        // @todo: Just for testing, get the first entry
//        if (is_array($value)) {
//            $value = $value[array_key_first($value)];
//        }
        if (false === $value instanceof \MongoDB\BSON\ObjectId) {
            // Ensure it's an ObjectId
            try {
                $value = new \MongoDB\BSON\ObjectId($value);
            } catch (\MongoDB\Driver\Exception\InvalidArgumentException $e) {
                $config['userDefinedValue'] = 'Error, not an ObjectId';
            }
        }
        // @todo: Lookup related model and get the name
        $config['userDefinedValue'] = (string)$value;


        return $config;
    }

}

// -- Polyfill for array_key_first as per https://www.php.net/manual/en/function.array-key-first.php
if (!function_exists('array_key_first')) {
    function array_key_first(array $arr)
    {
        foreach ($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}


