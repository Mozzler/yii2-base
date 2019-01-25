<?php
namespace mozzler\base\helpers;

class ModelHelper {

    /**
	 * Get the first matching scenario on a model, checking if it exists.
	 * 
	 * If the scenario is an array, iterate through all the scenarios
	 * trying to find the first scenario that matches
	 */
	public static function getModelScenario($model, $scenarios) {
		if (!$scenarios) {
			return;
		}

		if (is_string($scenarios)) {
			$scenarios = [$scenarios];
		}

		$modelScenarios = array_keys($model->scenarios());

		foreach ($scenarios as $scenario) {
			if (in_array($scenario, $modelScenarios)) {
				return $scenario;
			}
		}
	}

}