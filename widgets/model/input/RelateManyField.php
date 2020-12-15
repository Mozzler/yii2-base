<?php
namespace mozzler\base\widgets\model\input;

use kartik\select2\Select2 as BaseSelect2;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;
use yii\helpers\Html;
use mozzler\base\components\Tools;

class RelateManyField extends RelateOneField
{
    public function defaultConfig()
    {
        return [
            'widgetConfig' => [],
            'multiple' => true,
            'placeholder' => null
        ];
    }
	
}

