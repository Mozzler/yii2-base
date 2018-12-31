<?php
namespace mozzler\base\models;

use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\helpers\ArrayHelper;

use mozzler\rbac\mongodb\ActiveRecord as ActiveRecord;
use mozzler\base\helpers\FieldHelper;
use mozzler\base\helpers\ControllerHelper;

class Model extends ActiveRecord {

	public static $moduleClass = '\mozzler\base\Module';
	public $controllerRoute;
	
	protected static $collectionName;
	protected $modelFields;
	protected $modelConfig;
	
	const SCENARIO_CREATE = 'create';
	const SCENARIO_UPDATE = 'update';
	const SCENARIO_LIST = 'list';
	const SCENARIO_VIEW = 'view';
	const SCENARIO_SEARCH = 'search';
	const SCENARIO_EXPORT = 'export';
	
	const SCENARIO_CREATE_API = 'create-api';
	const SCENARIO_UPDATE_API = 'update-api';
	const SCENARIO_LIST_API = 'list-api';
	const SCENARIO_VIEW_API = 'view-api';
	
	public function init() {
		parent::init();
		
		$this->initModelConfig();
		$this->initModelFields();
		
		if (!$this->controllerRoute) {
			$className = self::className();
			preg_match('/([^\\\\]*)$/i', $className, $matches);
		
			if (sizeof($matches) == 2) {
				$this->controllerRoute = strtolower($matches[1]);
			}
			else {
				throw new \Exception('Unable to determine controller route for model '.$className);
			}
		}
	}
	
	protected function modelConfig() {
		return [
			'label' => 'Base Model',
			'labelPlural' => 'Base Models',
			// FUTURE: Support quick binding of behaviors, instead of overriding behaviors()
			/*'behaviors' => [
				'UserSetNameBehavior'
			]*/
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
            self::SCENARIO_SEARCH => ['id', 'name', 'createdUserId', 'updatedUserId'],
            self::SCENARIO_EXPORT => ['id', 'name', 'createdAt', 'createdUserId', 'updatedAt', 'updatedUserId'],
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
	
	/**
	 * Deny access to public users, which ensures
	 */
	public static function rbac() {
		return [
			'public' => [
				'find' => [
		            'grant' => false
		        ],
		        'insert' => [
		            'grant' => false
		        ],
		        'update' => [
		            'grant' => false
		        ],
		        'delete' => [
		            'grant' => false
		        ]
	        ],
	        'registered' => [
				'find' => [
		            'grant' => true
		        ],
		        'insert' => [
		            'grant' => true
		        ],
		        'update' => [
		            'grant' => true
		        ],
		        'delete' => [
		            'grant' => true
		        ]
	        ]
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
			// Handle 'id' as an alias for _id
			if ($key == 'id') {
				$key = '_id';
			}
			
			if (isset($this->modelFields[$key])) {
				return $this->modelFields[$key];
			}
		}
		
		return null;
	}
	
	public function fields()
	{
		// just use the current scenario fields
		return $this->activeAttributes();
	}
	
	public function attributes()
	{
		$attributes = [];
		$fields = $this->modelFields;
		
		if (!$fields) {
			return [];
		}
		
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
        
        $result['id'] = function($model) {
			return $model->getId();
		};

        if (empty($expand)) {
            return $result;
        }

        foreach ($this->extraFields() as $field => $definition) {
            $result[$field] = $field;
        }
		
        return $result;
    }
    
    /**
     * Get the route for an action on this model
     *
     * If a model has a custom route, the custom route will be returned.
     *
     * Defaults to returning a relative URL.
     *
     * @param	string		$action		Page action to obtain URL from (eg: view, list or update)
     * @param	array		$params		Array of additional GET parameters to append to the URL
     * @param	boolean		$absolute	Whether to return an absolute path
     * @return	string	URL for the requested action on this model
     */
    public function getUrl($action='view', $params=[], $absolute=false) {
	    $urlParams = ArrayHelper::merge([$this->controllerRoute.'/'.$action], $params);
	    if (!isset($urlParams['id']) && $this->id) {
		    $urlParams['id'] = $this->id;
	    }

		if ($absolute) {
			return \Yii::$app->urlManager->createAbsoluteUrl($urlParams);
		}
		else {
		    //return Url::toRoute($params);
		    return \Yii::$app->urlManager->createUrl($urlParams);
		}
    }

	public function getId() {
        return (string)$this->getPrimaryKey();
    }
    
    public static function collectionName()
    {
	    if (isset(static::$collectionName))
	    {
		    return static::$collectionName;
	    }
	    
	    return parent::collectionName();
    }
    
    /**
	 * Override the default getDirtyAttributes() method to remove
	 * any fields that shouldn't be saved to the database.
	 *
	 * Doing it this way allows fields to still be returned via a
	 * scenario (as the value isn't cleared from the model), but
	 * is never saved to the database as all the save() methods
	 * use getDirtyAttributes() to determine what to save.
	 */
    public function getDirtyAttributes($names = null) {
	    $attributes = parent::getDirtyAttributes($names);
	    
	    // only include attributes that should be saved
	    $finalAttributes = [];
	    foreach ($attributes as $key => $value) {
		    $field = $this->getModelField($key);
		    if ($field && $field->save) {
			    $finalAttributes[$key] = $value;
		    }
	    }
	    
	    return $finalAttributes;
    }
	
}