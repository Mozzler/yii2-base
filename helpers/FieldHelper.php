<?php
namespace mozzler\base\helpers;

use mozzler\components\FieldFactory;

class FieldHelper {
	
	public static function createFields($model, $fieldsConfig) {
		$fields = [];
		
		foreach ($fieldsConfig as $fieldName => $fieldConfig) {
			$fields[$fieldName] = self::createField($model, $fieldName, $fieldConfig);
		}
		
		return $fields;
	}
	
	private static function createField($model, $fieldName, $config) {
		if (!isset($config['type'])) {
			throw new \Exception("Field must define a type");
		}
		
		$type = $config['type'];
		unset($config['type']);
		
		$fieldTypes = \Yii::$app->mozzler->fieldTypes;
		
		if (!isset($fieldTypes[$type])) {
			throw new \Exception("Field type not found (".$type.")");
		}
		
		$config['attribute'] = $fieldName;
		$config['model'] = $model;
		
		return new $fieldTypes[$type]($config);
	}
	
}