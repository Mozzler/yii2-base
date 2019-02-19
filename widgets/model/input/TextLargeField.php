<?php
namespace mozzler\base\widgets\model\input;

use yii\helpers\ArrayHelper;

class TextLargeField extends BaseField
{

    public function defaultConfig()
	{
        return ArrayHelper::merge(parent::defaultConfig(), [
            'widgetConfig'=> [
                'rows' => 4
            ]
        ]);
    }

}

?>