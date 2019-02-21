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

		// Have a string, work out if its a comma separated list
		// of scenarios or just one
		if (is_string($scenarios)) {
			if (strstr($scenarios,',')) {
				// if the scenario contains a comma, split into multiple
				// scenarios based on the comma
				$scenarios = explode(',',$scenarios);
			} else {
				// We have a single scenario, so turn it into an array
				// so the search code still works
				$scenarios = [$scenarios];
			}
			
		}

		$modelScenarios = array_keys($model->scenarios());

		foreach ($scenarios as $scenario) {
			if (in_array($scenario, $modelScenarios)) {
				return $scenario;
			}
		}
	}

}