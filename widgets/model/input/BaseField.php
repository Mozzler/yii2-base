<?php
namespace mozzler\base\widgets\model\input;

use mozzler\base\widgets\BaseWidget;

class BaseField extends BaseWidget
{

    public function defaultConfig()
	{
        return [
            'model' => null,
            'attribute' => null,
            'fieldOptions' => []
        ];
    }

}
