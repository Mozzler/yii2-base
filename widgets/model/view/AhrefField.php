<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;
use yii\helpers\ArrayHelper;

class AhrefField extends BaseField
{
    /*
     * Example model usage:
     * function modelFields() { return  [
     * 'answerButtonLink' => [
            'type' => 'Text',
            'label' => 'Link',
            'required' => false,
            'widgets' => [
                'view' => [
                    'class' => 'mozzler\base\widgets\model\view\AhrefField',
                    'config' => [
                        'title' => 'Answer Link',
                        'target' => '_self',
                    ]
                ]
            ]
    ];}
    */
    public function defaultConfig()
    {
        $config = ArrayHelper::merge(parent::defaultConfig(), [
            'target' => '_blank',
            'title' => '', // Set the config if you want to change this
        ]);
        return $config;
    }
}

?>