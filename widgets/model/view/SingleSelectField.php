<?php
namespace mozzler\base\widgets\model\view;

use mozzler\base\widgets\model\view;

class SingleSelectField extends BaseField {
	
	public function config($templatify=true)
	{
		$config = parent::config();
		$modelField = $config['model']->getModelField($config['attribute']);
		$attribute = $config['attribute'];
        
		$labels = $modelField->getOptionLabels($config['model']->$attribute);
		$config['displayValue'] = join(", ",$labels);
		
		return $config;
    }
	
}

?>