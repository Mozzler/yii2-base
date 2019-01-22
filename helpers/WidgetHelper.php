<?php
namespace mozzler\base\helpers;

use yii\helpers\ArrayHelper;

class WidgetHelper extends \yii\base\Component {
	
	/**
	 * Given a widget Config, recursively iterate through it finding any twig templates
	 * and update the twig template strings with values from $data
	 */
	public static function templatifyConfig($config, $data=[], $twigConfig=[]) {
		$twigConfig = ArrayHelper::merge([
			'recursive' => true
		], $twigConfig);
		$t = new \mozzler\base\components\Tools;
		
		foreach ($config as $key => $value) {
			// if we have a string that contains the twig {{ tag
			if (is_string($value) && strpos($value,'{{') !== false) {
				try {
					$value = $t->renderTwig($value, $data, $twigConfig);
				} catch (\Twig_Error_Syntax $e) {
					// An error occurred with the config, so log a warning message
					\Yii::warning("Error occurred templatifying a widget configuration. Value is '{$value}' and error is '{$e->getMessage()}'");
				}
			} else if (is_array($value) && sizeof($value) > 0) {
				$value = self::templatifyConfig($value, $data, $twigConfig);
			}
			
			$config[$key] = $value;
		}
		
		return $config;
	}
	
}