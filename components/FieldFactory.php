<?php
namespace mozzler\base\components;

class FieldFactory extends \yii\base\Component {
	
	public static unction create($config) {
		if (!isset($config['type'])) {
			throw new \Exception("Field must define a type");
		}
		
		$type = $config['type'];
		unset($config['type']);
		
		$fieldTypes = \Yii::$app->params['fieldTypes'];
		
		if (!isset($fieldTypes[$type])) {
			throw new \Exception("Field type not found (".$fieldTypes[$type].")");
		}
		
		return new $fieldTypes[$type]($config);
	}
	
}
?>