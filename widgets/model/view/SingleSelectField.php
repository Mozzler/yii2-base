<?php
namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;

class SingleSelectField extends BaseField {
	
	public function config($templatify=true)
	{
		$config = parent::config();
		$modelField = $config['model']->getModelField($config['attribute']);
		$attribute = $config['attribute'];
		
		if (!isset($modelField->options[$config['model']->$attribute]))
		{
    		\Yii::warning("Unable to locate option ".$config['model']->$attribute." for field ".$attribute);
		}
		else
		{
    		$config['displayValue'] = $modelField->options[$config['model']->$attribute];
		}
		
		return $config;
	}
	
}

?>