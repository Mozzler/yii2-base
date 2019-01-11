<?php
namespace mozzler\base\components;

use yii\helpers\ArrayHelper;

use Yii;

class FieldGridConfig {
	
	public $config = [];
	
	public function defaultConfig() {
		return [
			'Base' => [],
			'Boolean' => [
				'class' => 'kartik\grid\BooleanColumn'
			],
			'Date' => [
				'class' => '\kartik\grid\DataColumn',
				'format' => ['date', 'php:'.Yii::$app->formatter->dateFormat]
			],
			'DateTime' => [
				'class' => '\kartik\grid\DataColumn',
				'format' => ['date', 'php:'.Yii::$app->formatter->datetimeFormat]
			],
			'Email' => [],
			'Integer' => [],
			'MongoId' => [],
			'Password' => [],
			'RelateOne' => [],
			'RelateMany' => [],
			'Text' => [],
			'TextLarge' => [],
			'Timestamp' => [
				'class' => '\kartik\grid\DataColumn',
				'format' => ['date', 'php:'.Yii::$app->formatter->datetimeFormat]
			],
			'SingleSelect' => [
				'class' => '\kartik\grid\EnumColumn'
			]
		];
	}
	
	public function fieldFunctions() {
		return [
			'SingleSelect' => function($field) {
				return [
					'enum' => $field->options
				];
			}
		];
	}
	
	/**
	 * Build config merging the defaults with any use supplied configuration
	 */
	public function config() {
		return ArrayHelper::merge($this->defaultConfig(), $this->config);
	}
	
	/**
	 * Build a Grid configuration for a field.
	 *
	 * @param $fieldType	Type of field (ie: Boolean)
	 * @param $customConfig	Custom configuration for this field
	 */
	public function getFieldConfig($field, $customConfig=[]) {
		$config = $this->config();
		
		if (!isset($config[$field->type])) {
			return $customConfig;
		}
		
		$config = ArrayHelper::merge($config[$field->type], $customConfig);
		$config['attribute'] = $field->attribute;
		
		$fieldFunctions = $this->fieldFunctions();
		if (isset($fieldFunctions[$field->type])) {
			$config = ArrayHelper::merge($fieldFunctions[$field->type]($field), $config);
		}
		
		return $config;
	}
	
}