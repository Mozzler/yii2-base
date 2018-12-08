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
			'Text' => [],
			'TextLarge' => [],
			'Timestamp' => [
				'class' => '\kartik\grid\DataColumn',
				'format' => ['date', 'php:'.Yii::$app->formatter->datetimeFormat]
			],
		];
		
		\Yii::trace(Yii::$app->formatter->datetimeFormat);
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
	public function getFieldConfig($fieldType, $attribute, $customConfig=[]) {
		$config = $this->config();
		
		if (!isset($config[$fieldType])) {
			return $customConfig;
		}
		
		$config = ArrayHelper::merge($config[$fieldType], $customConfig);
		$config['attribute'] = $attribute;
		return $config;
	}
	
}