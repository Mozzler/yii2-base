<?php

namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;
use yii\helpers\ArrayHelper;

class AhrefField extends BaseField
{

    public $title = '';
    public $target = '_blank';

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
                    'title' => 'Answer Link',
                    'target' => '_blank',
                ]
            ]
        ],
    ];}
    */
    public function config($templatify = false)
    {
        \Yii::warning("The AhrefField default config is using: " . var_export([
                'target' => $this->target,
                'title' => $this->title,
            ], true));
        return ArrayHelper::merge(parent::config(), [
            'target' => $this->target,
            'title' => $this->title,
        ]);
    }

}
