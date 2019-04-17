<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;
use yii\helpers\ArrayHelper;

class LinkField extends BaseField
{
    /*
     * Example model usage. On a model:
     * public function modelFields() { return  [
     * 'answerButtonLink' => [
            'type' => 'Text',
            'label' => 'Link',
            'required' => false,
            'widgets' => [
                'view' => [
                    'class' => 'mozzler\base\widgets\model\view\LinkField',
                    'options' => [
                        'title' => 'Answer Link',
                        'target' => '_self',
                    ]
                ]
            ]
    ];}
    */
    public function defaultConfig()
    {
        return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
            "tag" => "a",
            "options" => [
                "class" => "",
                "href" => '{{ widget.model[widget.attribute] }}',
                "title" => '',
                "target" => '_blank',
            ],
            "model" => null,
            "attribute" => null,
            'value' => '{{ widget.model[widget.attribute] }}'
        ]);
    }

    public function config($templatify = true)
    {
        $config = parent::config(true);
        return $config;
    }
}

?>