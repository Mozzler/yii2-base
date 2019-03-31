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
    public function defaultConfig()
    {
        return ArrayHelper::merge(parent::defaultConfig(), [
            'target' => $this->target,
            'title' => $this->title,
        ]);
    }

}

?>