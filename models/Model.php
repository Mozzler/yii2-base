<?php
namespace mozzler\base\models;

use \yii\mongodb\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

use mozzler\base\helpers\FieldHelper;

class Model extends ActiveRecord {

	public static $moduleClass = '\mozzler\base\Module';	
	protected static $collectionName;
	protected $modelFields;
	protected $modelConfig;
	
	const SCENARIO_CREATE = 'create';
	const SCENARIO_UPDATE = 'update';
	const SCENARIO_LIST = 'list';
	const SCENARIO_VIEW = 'view';
	const SCENARIO_SEARCH = 'search';
	const SCENARIO_EXPORT = 'export';
	
	public function init() {
		parent::init();
		
		$this->initModelConfig();
		$this->initModelFields();
	}
	
	protected function modelConfig() {
		return [
			'label' => 'Base Model',
			'labelPlural' => 'Base Models',
		];
	}
	
	protected function initModelConfig() {
		$this->modelConfig = $this->modelConfig();
	}
	
	public function scenarios()
    {
        return [
            self::SCENARIO_CREATE => ['name'],
            self::SCENARIO_UPDATE => ['name'],
            self::SCENARIO_LIST => ['name', 'createdUserId', 'createdAt'],
            self::SCENARIO_VIEW => ['name', 'createdUserId', 'createdAt', 'updatedUserId', 'updatedAt'],
            self::SCENARIO_SEARCH => ['_id', 'name', 'createdUserId', 'updatedUserId'],
            self::SCENARIO_EXPORT => ['_id', 'name', 'createdAt', 'createdUserId', 'updatedAt', 'updatedUserId'],
            self::SCENARIO_DEFAULT => array_keys($this->modelFields())
        ];
    }
	
	protected function initModelFields() {
		$this->modelFields = FieldHelper::createFields($this, $this->modelFields());
	}
	
	/**
	 * Define the available fields for this model, along with their configuration
	 */
	protected function modelFields() {
		return [
			'_id' => [
				'type' => 'MongoId',
				'label' => 'ID'
			],
			'name' => [
				'type' => 'Text',
				'label' => 'Name',
				'rules' => [
					'string' => [
						'max' => 255
					]
				]
			],
			'createdAt' => [
				'type' => 'Timestamp',
				'label' => 'Inserted'
			],
			'createdUserId' => [
				'type' => 'RelateOne',
				'label' => 'Created user',
				'config' => [
					'relatedField' => '_id',
					'relatedModel' => 'User'
				]
			],
			'updatedAt' => [
				'type' => 'Timestamp',
				'label' => 'Inserted'
			],
			'updatedUserId' => [
				'type' => 'RelateOne',
				'label' => 'Updated user',
				'config' => [
					'relatedField' => '_id',
					'relatedModel' => 'User'
				]
			],
		];
	}
	
	public function getModelConfig($key=null) {
		if ($key) {
			if (isset($this->modelConfig[$key])) {
				return $this->modelConfig[$key];
			}
			
			return null;
		}
		
		return $this->modelConfig;
	}
	
	public function getModelField($key=null) {
		if ($key) {
			if (isset($this->modelFields[$key])) {
				return $this->modelFields[$key];
			}
		}
		
		return null;
	}
	
	public function fields()
	{
		// TODO: return fields based on this->modelFields;
	}
	
	public function attributes()
	{
		$attributes = [];
		$fields = $this->modelFields;
		
		foreach ($fields as $fieldKey => $field) {
			$attributes[] = $fieldKey;
		}
		
		return $attributes;
	}
	
	public function attributeLabels()
	{
		$labels = [];
		$fields = $this->modelFields;
		
		foreach ($fields as $fieldKey => $field) {
			$labels[$fieldKey] = \Yii::t('app', $field->label);
		}
		
		return $labels;
	}
	
	/**
	 * Build field rules from fields() configuration
	 */
	public function rules()
	{
		$rules = [];
		$fields = $this->modelFields;
		
		foreach ($fields as $fieldKey => $field) {
			foreach ($field->rules() as $validator => $fieldRules) {
				$rule = [$fieldKey, $validator];
				$rule = array_merge($rule, $fieldRules);
			}
			
			if (isset($rule)) {
				$rules[] = $rule;
			}
		}
		
		return $rules;
	}
	
	/**
	 * Helper method to load default values for all fields in this model
	 */
	public function loadDefaultValues() {
		foreach ($this->modelFields as $fieldName => $field) {
			if (!isset($this->$fieldName) && $field->default != null) {
				$this->$fieldName = $field->default;
			}
		}
	}
	
	public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['createdAt', 'updatedAt'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updatedAt'],
                ],
            ],
            [
            	'class' => BlameableBehavior::className(),
            	'createdByAttribute' => 'createdUserId',
            	'updatedByAttribute' => 'updatedUserId',
            ]
        ];
    }
    
    /**
	 * Add support for Rappsio internals
	 * 
	 * @ignore
	 * @internal Override yii2/base/ArrayableTrait.php to support this objects custom fields() method
	 */
	protected function resolveFields(array $fields, array $expand) {
        $result = [];

        foreach ($this->modelFields as $field => $definition) {
        	if ($fields && !in_array($field, $fields))
        		continue;

            $result[$field] = $field;
        }

        if (empty($expand)) {
            return $result;
        }

        foreach ($this->extraFields() as $field => $definition) {
            $result[$field] = $field;
        }
		
        return $result;
    }
	
}