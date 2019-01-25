<?php
namespace mozzler\base\models;

use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\helpers\ArrayHelper;

use mozzler\rbac\mongodb\ActiveRecord as ActiveRecord;
use mozzler\base\helpers\FieldHelper;
use mozzler\base\helpers\ModelHelper;

use yii\data\ActiveDataProvider;
use yii\data\ActiveDataFilter;

class Model extends ActiveRecord {

	public static $moduleClass = '\mozzler\base\Module';
	public $controllerRoute;
	
	protected static $collectionName;
	protected $modelFields;
	protected $modelConfig;
	
	const SCENARIO_CREATE = 'create';
	const SCENARIO_UPDATE = 'update';
	const SCENARIO_DELETE = 'delete';
	const SCENARIO_LIST = 'list';
	const SCENARIO_VIEW = 'view';
	const SCENARIO_SEARCH = 'search';
	const SCENARIO_EXPORT = 'export';
	const SCENARIO_DEFAULT = 'default';

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
				// transform Controller Name to be a valid URL form
				// lowercase & hyphenated before an uppercase if camelCase
				// e.g. User => user | SystemLog => system-log
				// *Note: CamelCASE => camel-c-a-s-e
				$this->controllerRoute = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $matches[1]));
			}
			else {
				throw new \Exception('Unable to determine controller route for model '.$className);
			}
		}
	}
	
	protected function modelConfig()
	{
		return [
			'label' => 'Base Model',
			'labelPlural' => 'Base Models',
			// FUTURE: Support quick binding of behaviors, instead of overriding behaviors()
			/*'behaviors' => [
				'UserSetNameBehavior'
			]*/
		];
	}
	
	protected function initModelConfig()
	{
		$this->modelConfig = $this->modelConfig();
	}
	
	/**
	 * Specifies the indexes of a Model.
	 * 
	 * Sample model class:
	 * return ArrayHelper::merge(parent::modelIndexes(), [
	 *		'nameUnique' => [
	 *			'columns' => ['name' => 1],
	 *			'options' => [
	 *				'unique' => 0
	 *			],
	 *			'duplicateMessage' => ['Name already exists in the collection']
	 *		]
	 *	]); 
	 * 
	 * Indexes defined in the model class will be synced to MongoDB Collection
	 * through mozzler\base\components\IndexManager class
	 * where it automatically detects:
	 * 	- New indexes
	 *  - Updated indexes
	 *  - Deleted indexes
	 */
	public static function modelIndexes()
	{
    	return [];
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
            self::SCENARIO_DELETE => ['id', 'name', 'createdAt', 'updatedAt'],
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
				'relatedField' => '_id',
				'relatedModel' => 'app\models\User'
			],
			'updatedAt' => [
				'type' => 'Timestamp',
				'label' => 'Updated'
			],
			'updatedUserId' => [
				'type' => 'RelateOne',
				'label' => 'Updated user',
				'relatedField' => '_id',
				'relatedModel' => 'app\models\User'
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
	 * Add support for internals
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
        
        $result = array_merge(['id' => function($model) {
			return $model->getId();
		}], $result);

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
			    $finalAttributes[$key] = $field->setValue($value);
		    }
	    }
	    
	    return $finalAttributes;
    }

    /**
	 * Get related models
	 *
	 * @param	string	$attribute	Related field attribute to get (excluding `_id`)
	 * @param	array	$filter		MongoDB filter to apply to the query
	 * @param	int		$limit		Maximum number of results to return
	 * @param	int		$offset		Offset position for results
	 * @param	array	$orderBy	The columns (and the directions) to be ordered by. (eg `{_id: r.getConstant("db.SORT_ASC)}`)
	 * @param	array	$fields		Array of fields to return. Returns all if empty array supplied (default).
	 * @param	boolean	$checkPermissions	Whether to check permissions based on the logged in user when running the query
	 * @return	array	Returns an array of related models. If none found, returns an empty array. If no matching related field found, returns `false`.
	 */
    public function getRelated($attribute, $filter=[], $limit=null, $offset=null, $orderBy=null, $fields=null, $checkPermissions=null) {
		if ($this->getModelField($attribute)) {
			$field = $this->getModelField($attribute);
		} else {
			$attribute = $attribute.'Id';
			$field = $this->getModelField($attribute.'Id');
		}

	    if ($field) {
			switch ($field->type) {
				case 'RelateMany':

					$config = $field->relationDefaults;
					foreach ($config as $k => $default) {
						if ($$k !== null) {
							$config[$k] = $$k;
						}
					}
					return $this->getRelatedMany($field->relatedModel, $field->relatedField, $field->linkField, $config['filter'], $config['limit'], $config['offset'], $config['orderBy'], $config['fields'], $config['checkPermissions']);
					break;

    			case 'RelateManyMany':
					return $this->getRelatedManyMany($field->relatedModel, $field->relatedField, $field->linkField, $field->relatedFieldMany, $config['filter'], $config['limit'], $$config['offset'], $config['orderBy'], $config['fields'], $config['checkPermissions']);
					break;

				case 'RelateOne':
					$relatedModelNamespace = $field->relatedModel;
					if (isset($field->relatedModelField)) {
						// This may be a flexible field that can be related to multiple model types
						$relatedModelField = $field->relatedModelField;

						if ($this->$relatedModelField) {
							$relatedModelNamespace = $this->$relatedModelField;
						}
					}

					return $this->getRelatedOne($relatedModelNamespace, $attribute, $checkPermissions);
					break;
				
				default:
					return false;
			}
    	}
    	
    	return false;
    }
    
    /**
     *
	 */
    protected function getRelatedOne($modelClass, $fieldFrom, $checkPermissions=true) {
		\Yii::createObject($modelClass);
        $query = $this->hasOne($modelClass, ['_id' => $fieldFrom]);
        $query->checkPermissions = $checkPermissions;
        return $query->one();
    }
    
    /**
	 * @todo Can this be protected?
	 * @see getRelated()
	 * @return	array	Returns an array of related models. If none found, returns an empty array. If no matching related field found, returns `false`.
	 */
    public function getRelatedMany($modelClass, $fieldFrom, $fieldTo, $filter=[], $limit=20, $offset=null, $orderBy=[], $fields=[], $checkPermissions=true) {
		$relatedClass = \Yii::createObject($modelClass);
        $query = $this->hasMany($modelClass, [$fieldFrom => $fieldTo]);
        $query->checkPermissions = $checkPermissions;

		return $this->buildQuery($query, $filter, $limit, $offset, $orderBy, $fields)->all();
    }
    
    /**
	 * Add support for internals
	 *
	 * @ignore
	 */
	protected function getRelatedManyMany($modelClass, $fieldFrom, $fieldTo, $fieldToMany, $filter=[], $limit=20, $offset=null, $orderBy=[], $fields=[], $checkPermissions=true) {
		// get all related object ids without filtering OR
		// use special parameter "$many" as filter
		$manyFilter = [];
		if (isset($filter['$many'])) {
			$manyFilter = $filter['$many'];
			unset($filter['$many']);
		}
		
		$relatedObjects = $this->getRelatedMany($modelClass, $fieldFrom, $fieldTo, $manyFilter, null, null, [], [$fieldToMany], false);		
		$ids = [];
		foreach ($relatedObjects as $record)
			$ids[] = (string) $record->$fieldToMany;
		
		if (sizeof($ids) == 0)
			return [];
		
		// related class = many:many join collection
		$relatedClass = \Yii::createObject($modelClass);
		$relatedField = $relatedClass->getField($fieldToMany);
		$relatedModel = \Yii::createObject($relatedField->relatedModel);
		
		if (!isset($filter['_id'])) {
			$filter['_id'] = [
				'$in' => $ids
			];
		}
		
		// find all related models, but this time apply filtering etc.
		$query = $relatedModel->find($checkPermissions);
		return $this->buildQuery($query, $filter, $limit, $offset, $orderBy, $fields)->all();
	}
	
	/**
	 * Add support for internals
	 *
	 * @ignore
	 * @todo Restrict by fields!
	 */
	protected function buildQuery($query, $filter=[], $limit=20, $offset=null, $orderBy=[], $fields=[]) {
		if ($filter)
	        $query->where = $filter;
	    
	    if ($limit)
	    	$query->limit = $limit;
	    
	    if ($offset)
	    	$query->offset = $offset;
	    
	    if ($orderBy)
	    	$query->orderBy = $orderBy;
	    
	    return $query;
	}
	
	/**
     * Add support for permission checks
     *
     * @see \yii\base\Model::beforeSave()
     * @param	boolean		$checkPermissions	Whether to check permissions based on the current logged in user.
     */
	public static function find($checkPermissions=true, $applyDefaultFilter=true) {
		$query = \Yii::createObject('yii\mongodb\ActiveQuery', [get_called_class()]);
		$query->checkPermissions = $checkPermissions;
		
		/*if (isset(static::$modelDefaultFilter) && $applyDefaultFilter) {
			$query->defaultFilter = static::$modelDefaultFilter;
		}*/
		
		return $query;
    }
    
    /**
     * Add support for permission checks
     *
     * @see \yii\base\Model::beforeSave()
     * @param	boolean		$checkPermissions	Whether to check permissions based on the current logged in user.
     */
    public static function findOne($condition, $checkPermissions=true, $applyDefaultFilter=true) {
        return static::findByCondition($condition, $checkPermissions, $applyDefaultFilter)->one();
    }
    
    /**
     * Add support for permission checks
     *
     * @ignore
     * @see \yii\base\Model::beforeSave()
     * @param	boolean		$checkPermissions	Whether to check permissions based on the current logged in user.
     */
    protected static function findByCondition($condition, $checkPermissions=true, $applyDefaultFilter=true) {
        $query = static::find($checkPermissions, $applyDefaultFilter);

        if (!ArrayHelper::isAssociative($condition)) {
            // query by primary key
            $primaryKey = static::primaryKey();
            if (isset($primaryKey[0])) {
                $condition = [$primaryKey[0] => $condition];
            } else {
                throw new InvalidConfigException('"' . get_called_class() . '" must have a primary key.');
            }
        }
        
        return $query->andWhere($condition);
    }
    
    /**
	 * Build a DataProvider that has a query filtering by the
	 * data provided in $params
	 */
	public function search($params=[]) {
		// create a query from the parent model
		$query = $this->find();
		
		// load the parameters into this model and continue
		// if the model validates
		if ($this->load($params)) {
			// iterate through the search attributes building a generic filter array
			$filterParams = ['and' => []];
			$attributeFilters = [];
			foreach ($this->attributes() as $attribute) {
    			$modelField = $this->getModelField($attribute);
				if ($this->$attribute) {
					$attributeFilters[] = $modelField->generateFilter($this, $attribute);
				}
			}
			
			// if we have filters to apply, build a filter condition that can
			// be added to the query
			if (sizeof($attributeFilters) > 0) {
				$filterParams['and'] = $attributeFilters;
			
				$params = ['filter' => $filterParams];
				$dataFilter = new ActiveDataFilter([
					'searchModel' => $this
				]);
			
				$filterCondition = null;
				$dataFilter->load($params);
		        if ($dataFilter->validate()) {
		            $filterCondition = $dataFilter->build();
		            
		            // if we have a valid filter condition, add it to the query
					if ($filterCondition !== null) {
						$query->andWhere($filterCondition);
					}
		        } else {		        
			        \Yii::warning('Search filter isn\'t valid: '.print_r($dataFilter->getErrors()['filter'],true));
			        \Yii::warning(print_r($filterParams,true));
			    }
			}
		}
		
		return new ActiveDataProvider([
			'query' => $query,
		]);
	}
	
	/**
	 * Set the scenario, but support an array of scenarios to check
	 */
	public function setScenario($scenario) {
		parent::setScenario(ModelHelper::getModelScenario($this, $scenario));
	}
	
}