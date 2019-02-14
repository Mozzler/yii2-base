<?php
namespace mozzler\base\widgets\model\input;

use yii\helpers\ArrayHelper;
use kartik\datecontrol\DateControl;

class DateTimeField extends BaseField
{

    public function defaultConfig() {
        return ArrayHelper::merge(parent::defaultConfig(), [
            'widgetConfig' => [
                'type' => DateControl::FORMAT_DATETIME,
                'displayFormat' => 'php:'.\Yii::$app->formatter->datetimeFormat
            ]
        ]);
    }
    
    public function run() {
        $config = $this->config();
        $form = $config['form'];
        $model = $config['model'];
        $attribute = $config['attribute'];

        $field = $form->field($model, $attribute);
		
		return $field->widget(DateControl::className(), $config['widgetConfig']);
	}
	
}

?>