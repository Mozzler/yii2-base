<?php
namespace mozzler\base\models;

use \yii\mongodb\ActiveRecord;
use mozzler\components\FieldFactory as FF;

class Base extends ActiveRecord {

	public static $moduleClass = '\mozzler\base\Module';	
	protected static $collectionName;
	
	const SCENARIO_CREATE = 'create';
	const SCENARIO_UPDATE = 'update';
	const SCENARIO_LIST = 'list';
	const SCENARIO_VIEW = 'view';
	const SCENARIO_SEARCH = 'search';
	const SCENARIO_EXPORT = 'export';
	
	public function config() {
		return [
			'label' => 'Base Model',
			'labelPlural' => 'Base Models',
		];
	}
	
	public function fields()
	{
		return [
			'_id' => FF:create([
				'type' => 'MongoId',
				'label' => 'ID'
			]),
			'name' => FF:create([
				'type' => 'Text',
				'label' => 'Name',
				'rules' => [
					'string' => [
						'max' => 255
					]
				]
			]),
			'_id' => FF:create([
				'type' => 'MongoId',
				'label' => 'ID'
			]),
			'_id' => FF:create([
				'type' => 'MongoId',
				'label' => 'ID'
			]),
		]
	}
	
	public function scenarios()
    {
        return [
            self::SCENARIO_CREATE => ['name'],
            self::SCENARIO_UPDATE => ['name'],
            self::SCENARIO_LIST => ['name', 'insertedUserId', 'inserted'],
            self::SCENARIO_VIEW => ['name', 'insertedUserId', 'inserted', 'updatedUserId', 'updated'],
            self::SCENARIO_SEARCH => ['_id', 'name', 'insertedUserId', 'updatedUserId'],
            self::SCENARIO_EXPORT => ['_id', 'name', 'inserted', 'insertedUserId', 'updated', 'updatedUserId'],
            self::SCENARIO_DEFAULT => array_keys($this->fields())
        ];
    }
	
	/**
	 * Build field rules from fields() configuration
	 */
	public function rules()
	{
		$rules = [];
		$fields = $this->fields();
		
		foreach ($fields as $fieldKey => $fieldValue) {
			if (isset($fieldValue['rules'])) {
				$rules[$fieldKey] = $fieldValue['rules'];
			}
		}
		
		return $rules;
	}
	
}