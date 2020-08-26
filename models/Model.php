<?php

namespace mozzler\base\models;

use MongoDB\BSON\ObjectId;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use mozzler\base\models\behaviors\AutoIncrementBehavior;
use yii\helpers\ArrayHelper;

use mozzler\rbac\mongodb\ActiveRecord as ActiveRecord;
use mozzler\base\helpers\FieldHelper;
use mozzler\base\helpers\ModelHelper;

use yii\data\ActiveDataProvider;
use mozzler\base\yii\data\ActiveDataFilter;
use yii\helpers\VarDumper;

/**
 * Class Model
 * @package mozzler\base\models
 *
 * @property ObjectId $_id
 * @property string $name
 * @property integer $createdAt
 * @property integer $updatedAt
 * @property ObjectId $createdUserId
 * @property ObjectId $updatedUserId
 */
class Model extends ActiveRecord
{

    public static $moduleClass = '\mozzler\base\Module';
    public $controllerRoute;

    protected static $collectionName;
    protected $modelFields;
    public $modelConfig;

    const SCENARIO_CREATE = 'create';
    const SCENARIO_UPDATE = 'update';
    const SCENARIO_DELETE = 'delete';
    const SCENARIO_LIST = 'list';
    const SCENARIO_SUBPANEL = 'subpanel';
    const SCENARIO_VIEW = 'view';
    const SCENARIO_SEARCH = 'search';
    const SCENARIO_EXPORT = 'export';
    const SCENARIO_DEFAULT = 'default';
    const SCENARIO_AUDITABLE = 'auditable'; // Which fields to save to the auditLog, if the AuditLogBehaviour has been attached

    const SCENARIO_CREATE_API = 'create-api';
    const SCENARIO_UPDATE_API = 'update-api';
    const SCENARIO_LIST_API = 'list-api';
    const SCENARIO_VIEW_API = 'view-api';
    const SCENARIO_SEARCH_API = 'search-api';

    public function init()
    {
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
            } else {
                throw new \Exception('Unable to determine controller route for model ' . $className);
            }
        }
    }

    protected function modelConfig()
    {
        return [
            'label' => 'Base Model',
            'labelPlural' => 'Base Models',
            'searchAttribute' => 'name'
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
     *        'nameUnique' => [
     *            'columns' => ['name' => 1],
     *            'options' => [
     *                'unique' => 0
     *            ],
     *            'duplicateMessage' => ['Name already exists in the collection']
     *        ]
     *    ]);
     *
     * Indexes defined in the model class will be synced to MongoDB Collection
     * through mozzler\base\components\IndexManager class
     * where it automatically detects:
     *    - New indexes
     *  - Updated indexes
     *  - Deleted indexes
     */
    public function modelIndexes()
    {
        $indexes = [];

        // Automatically generate unique indexes for auto increment fields
        foreach ($this->modelFields() as $attribute => $fieldConfig) {
            if ($fieldConfig['type'] == 'AutoIncrement') {
                $indexes['autoIncrement' . ucfirst($attribute)] = [
                    'columns' => [$attribute => -1],
                    'options' => [
                        'unique' => 1
                    ],
                    'attribute' => $attribute,
                    'autoIncrement' => true,
                    'duplicateMessage' => 'Autoincrement collision, will try again'
                ];
            }
        }

        return $indexes;
    }

    public function scenarios()
    {

        return [
            self::SCENARIO_CREATE => ['name'],
            self::SCENARIO_UPDATE => ['name'],
            self::SCENARIO_LIST => ['name', 'createdUserId', 'createdAt'],
            self::SCENARIO_VIEW => ['name', 'createdUserId', 'createdAt', 'updatedUserId', 'updatedAt'],
            self::SCENARIO_SEARCH => ['id', 'name', 'createdUserId', 'updatedUserId'],
            self::SCENARIO_EXPORT => array_keys(array_filter($this->getCachedModelFields(), function ($modelField, $modelKey) {
                if ($modelKey === 'id') {
                    return false; // Only want '_id' not 'id' otherwise it's doubling up
                }
                // This is used by the CSV export e.g model/export so you don't want to output fields that are relateMany
                return $modelField['type'] === 'RelateMany' ? false : true;
            }, ARRAY_FILTER_USE_BOTH)),
            self::SCENARIO_DELETE => ['id', 'name', 'createdAt', 'updatedAt'],

            self::SCENARIO_DEFAULT => array_keys($this->getCachedModelFields()),
            self::SCENARIO_AUDITABLE => array_values(array_diff(array_keys($this->getCachedModelFields()), ['updatedAt', 'createdAt', 'createdUserId', 'updatedUserId'])), // Default to all fields except the updated and created auto-generated fields. Note the use of array_values to repack the array after array_diff removes the entries
        ];
    }

    /**
     * @return array
     * We cache the model field results within the request
     */
    protected function getCachedModelFields()
    {
        $sessionCache = \Yii::$app->t->getRequestCache();
        $sessionKey = $this::$collectionName . '-modelField';
        if ($sessionCache->exists($sessionKey)) {
            return $sessionCache->get($sessionKey);
        }
        $modelFields = $this->modelFields();
        $sessionCache->set($sessionKey, $modelFields);
        return $modelFields;
    }

    protected function initModelFields()
    {
        $this->modelFields = FieldHelper::createFields($this, $this->getCachedModelFields());
    }


    /**
     * Define the available fields for this model, along with their configuration
     */
    protected function modelFields()
    {
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
                'label' => 'Created Time'
            ],
            'createdUserId' => [
                'type' => 'RelateOne',
                'label' => 'Created User',
                'relatedField' => '_id',
                'relatedModel' => 'app\models\User'
            ],
            'updatedAt' => [
                'type' => 'Timestamp',
                'label' => 'Updated Time'
            ],
            'updatedUserId' => [
                'type' => 'RelateOne',
                'label' => 'Updated User',
                'relatedField' => '_id',
                'relatedModel' => 'app\models\User'
            ],
        ];
    }

    /**
     * Deny access to public users, which ensures
     */
    public static function rbac()
    {
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
            ],
            'admin' => [
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

    public function getModelConfig($key = null)
    {
        if ($key) {
            if (isset($this->modelConfig[$key])) {
                return $this->modelConfig[$key];
            }

            return null;
        }

        return $this->modelConfig;
    }

    public function getModelField($key = null)
    {
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

        foreach ($fields as $fieldName => $field) {
            foreach ($field->rules() as $validator => $fieldRules) {
                if (is_array($fieldRules)) {
                    if (isset($fieldRules[0])) {
                        foreach ($fieldRules as $newRule) {
                            $rule = array_merge([$fieldName, $validator], $newRule);
                            $rules[] = $rule;
                        }
                    } else {
                        $rule = array_merge([$fieldName, $validator], $fieldRules);
                        $rules[] = $rule;
                    }
                } else {
                    // fieldRules is actually a custom validator, so just add to the rules
                    $rule = [$fieldName, $fieldRules];
                    $rules[] = $rule;
                }
            }
        }

        return $rules;
    }

    public function attributeHints()
    {
        $hints = [];
        $fields = $this->modelFields;

        foreach ($fields as $fieldName => $field) {
            if ($field->hint) {
                $hints[$fieldName] = $field->hint;
            }
        }

        return $hints;
    }

    /**
     * Helper method to load default values for all fields in this model
     */
    public function loadDefaultValues()
    {
        foreach ($this->modelFields as $fieldName => $field) {
            if (!isset($this->$fieldName) && $field->default != null) {
                $this->$fieldName = $field->default;
            }
        }
    }

    public function behaviors()
    {
        return [
            'timestamps' => [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'createdAt',
                'updatedAtAttribute' => 'updatedAt'
            ],
            'blameable' => [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'createdUserId',
                'updatedByAttribute' => 'updatedUserId',
            ],
            'autoincrement' => [
                'class' => AutoIncrementBehavior::className(),
                'autoIncrementAttributes' => $this->autoIncrementAttributes()
            ]
        ];
    }

    public function __get($key)
    {
        $modelField = $this->getModelField($key);
        if ($modelField && $modelField->formula) {
            $formula = $modelField->formula;
            return $formula($this);
        }

        return parent::__get($key);
    }

    /**
     * Add support for internals
     *
     * @ignore
     * @internal Override yii2/base/ArrayableTrait.php to support this objects custom fields() method
     */
    protected function resolveFields(array $fields, array $expand)
    {
        $result = [];

        foreach ($this->modelFields as $field => $definition) {
            if ($fields && !in_array($field, $fields))
                continue;

            $result[$field] = $field;
        }

        $result = array_merge(['id' => function ($model) {
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
     * @param string $action Page action to obtain URL from (eg: view, list or update)
     * @param array $params Array of additional GET parameters to append to the URL
     * @param boolean $absolute Whether to return an absolute path
     * @return    string    URL for the requested action on this model
     */
    public function getUrl($action = 'view', $params = [], $absolute = false)
    {
        $urlParams = ArrayHelper::merge([$this->controllerRoute . '/' . $action], $params);
        if (!isset($urlParams['id']) && $this->id) {
            $urlParams['id'] = $this->id;
        }

        if ($absolute) {
            return \Yii::$app->urlManager->createAbsoluteUrl($urlParams);
        } else {
            //return Url::toRoute($params);
            return \Yii::$app->urlManager->createUrl($urlParams);
        }
    }

    public function getId()
    {
        return (string)$this->getPrimaryKey();
    }

    public static function collectionName()
    {
        if (isset(static::$collectionName)) {
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
    public function getDirtyAttributes($names = null)
    {
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
     * @param string $attribute Related field attribute to get (excluding `_id`)
     * @param array $filter MongoDB filter to apply to the query
     * @param int $limit Maximum number of results to return
     * @param int $offset Offset position for results
     * @param array $orderBy The columns (and the directions) to be ordered by. (eg `{_id: r.getConstant("db.SORT_ASC)}`)
     * @param    array    $fields        Array of fields to return. Returns all if empty array supplied (default).
     * @param boolean $checkPermissions Whether to check permissions based on the logged in user when running the query
     * @return    array    Returns an array of related models. If none found, returns an empty array. If no matching related field found, returns `false`.
     */
    public function getRelated($attribute, $filter = [], $limit = null, $offset = null, $orderBy = null, $fields = null, $checkPermissions = null)
    {
        if ($this->getModelField($attribute)) {
            $field = $this->getModelField($attribute);
        } else {
            $attribute = $attribute . 'Id';
            $field = $this->getModelField($attribute);
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
    protected function getRelatedOne($modelClass, $fieldFrom, $checkPermissions = true)
    {
        \Yii::createObject($modelClass);
        $query = $this->hasOne($modelClass, ['_id' => $fieldFrom]);
        $query->checkPermissions = $checkPermissions;
        return $query->one();
    }

    /**
     * @return    array    Returns an array of related models. If none found, returns an empty array. If no matching related field found, returns `false`.
     * @see getRelated()
     * @todo Can this be protected?
     */
    public function getRelatedMany($modelClass, $fieldFrom, $fieldTo, $filter = [], $limit = 20, $offset = null, $orderBy = [], $fields = [], $checkPermissions = true)
    {
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
    protected function getRelatedManyMany($modelClass, $fieldFrom, $fieldTo, $fieldToMany, $filter = [], $limit = 20, $offset = null, $orderBy = [], $fields = [], $checkPermissions = true)
    {
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
            $ids[] = (string)$record->$fieldToMany;

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
    protected function buildQuery($query, $filter = [], $limit = 20, $offset = null, $orderBy = [], $fields = [])
    {
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
     * @param boolean $checkPermissions Whether to check permissions based on the current logged in user.
     * @see \yii\base\Model::beforeSave()
     */
    public static function find($checkPermissions = true, $applyDefaultFilter = true)
    {
        $query = \Yii::createObject('yii\mongodb\ActiveQuery', [get_called_class()]);
        $query->checkPermissions = $checkPermissions;

        /*if (isset(static::$modelDefaultFilter) && $applyDefaultFilter) {
            $query->defaultFilter = static::$modelDefaultFilter;
        }*/

        return $query;
    }


    /**
     * Returns all the found results, instead of the query itself
     *
     * @param array $condition The filtering to do
     * @param boolean $checkPermissions Whether to check permissions based on the current logged in user.
     * @param boolean $applyDefaultFilter If the default filter should apply (RBAC)
     * @return \mozzler\base\models\Model[] Returns an array containing the requested objects
     * @see \yii\base\Model::beforeSave()
     */
    public static function findAll($condition, $checkPermissions = true, $applyDefaultFilter = true)
    {
        return static::findByCondition($condition, $checkPermissions, $applyDefaultFilter)->all();
    }

    /**
     * Add support for permission checks
     *
     * @param boolean $checkPermissions Whether to check permissions based on the current logged in user.
     * @see \yii\base\Model::beforeSave()
     */
    public static function findOne($condition, $checkPermissions = true, $applyDefaultFilter = true)
    {
        return static::findByCondition($condition, $checkPermissions, $applyDefaultFilter)->one();
    }

    /**
     * Add support for permission checks
     *
     * @param boolean $checkPermissions Whether to check permissions based on the current logged in user.
     * @see \yii\base\Model::beforeSave()
     * @ignore
     */
    protected static function findByCondition($condition, $checkPermissions = true, $applyDefaultFilter = true)
    {
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
     * data provided in $params (supplied by a form, so is keyed by
     * $form->formName)
     */
    public function search($params = [], $scenario = null, $queryFilter = [], $dataProviderConfig = [])
    {
        if (!$scenario) {
            $scenario = self::SCENARIO_DEFAULT;
        }

        // Create a query from the parent model
        $model = clone $this;
        $model->setScenario($scenario);
        $query = $model->find();

        if ($queryFilter && is_array($queryFilter)) {
            $query->andWhere($queryFilter);
        }

        // Load any form parameters into this model
        $model->load($params);

        // Iterate through the search attributes building a generic filter array
        $filterParams = ['and' => []];
        $attributeFilters = [];

        $searchAttributes = $model->attributes();

        foreach ($searchAttributes as $attribute) {
            $modelField = $this->getModelField($attribute);
            if ($model->$attribute) {
                $attributeFilters[] = $modelField->generateFilter($model, $attribute, $params);
            }
        }

        // If we have filters to apply, build a filter condition that can
        // be added to the query
        if (sizeof($attributeFilters) > 0) {
            $filterParams['and'] = $attributeFilters;

            $params = ['filter' => $filterParams];
            $dataFilter = new ActiveDataFilter([
                'searchModel' => $model
            ]);

            $filterCondition = null;
            $dataFilter->load($params);
            $filterCondition = $dataFilter->build(false);

            // if we have a valid filter condition, add it to the query
            if ($filterCondition !== null) {
                $query->andWhere($filterCondition);
            }
        }

        // Return an ActiveDataProvider merging any default data provider config
        return new ActiveDataProvider(ArrayHelper::merge($dataProviderConfig, [
            'query' => $query,
        ]));
    }

    /**
     * Set the scenario, but support an array of scenarios to check
     */
    public function setScenario($scenario)
    {
        parent::setScenario(ModelHelper::getModelScenario($this, $scenario));
    }


    /**
     * To Scenario Array
     *
     * @param array $fields
     * @param array $expand
     * @param bool $recursive
     * @return mixed
     *
     * Unlike the toArray() method, this automatically adds the fields defined in the scenario.
     * This assumes that the set scenario is a valid one that's been defined.
     *
     * Example usage:
     *  $user->setScenario(User::SCENARIO_AUTHENTICATE);
     *  return $user->toScenarioArray();
     */
    public function toScenarioArray(array $fields = [], array $expand = [], $recursive = true)
    {
        // Add the current scenario to the fields being requested
        $fields = ArrayHelper::merge($this->scenarios()[$this->getScenario()], $fields);
        return $this->toArray($fields, $expand, $recursive);
    }

    public function save($runValidation = true, $attributeNames = null, $checkPermissions = true)
    {
        try {
            if ($this->getIsNewRecord()) {
                return $this->insert($runValidation, $attributeNames, $checkPermissions);
            } else {
                return $this->update($runValidation, $attributeNames, $checkPermissions) !== false;
            }
        } catch (\yii\mongodb\Exception $e) {
            $code = (int)$e->getCode();

            switch ($code) {
                case 11000:
                    // duplicate key exception
                    preg_match('/index: (\w*) dup key/', $e->getMessage(), $results);
                    $indexName = trim($results[1]);

                    $modelIndexes = $this->modelIndexes();
                    if (!isset($modelIndexes[$indexName])) {
                        // haven't been able to find this index, so rethrow the exception
                        throw $e;
                    }

                    $foundIndex = $modelIndexes[$indexName];

                    // handle if the duplicate key exception has been caused by an
                    // autoincrement field. See mozzler\base\fields\AutoIncrement.php
                    // note: this is recursive and will continue until insertion can occur
                    if ($this->getIsNewRecord()) {
                        if (isset($foundIndex['autoIncrement']) && $foundIndex['autoIncrement']) {
                            $fieldName = $foundIndex['attribute'];
                            $this->$fieldName = intval($this->$fieldName) + 1;

                            // Try saving again, but don't re-validate
                            return $this->save(false, null, $checkPermissions);
                        }
                    }

                    $message = "Duplicate key for index: " . $indexName;
                    if (isset($foundIndex['duplicateMessage'])) {
                        $message = $foundIndex['duplicateMessage'];
                    }

                    $this->addError($results[1], $message);
                    return false;
                    break;
                default:
                    // rethrow the exception
                    throw $e;
            }
        }
    }

    protected function autoIncrementAttributes()
    {
        $autoIncrementAttributes = [];

        foreach ($this->getCachedModelFields() as $attribute => $fieldConfig) {
            if ($fieldConfig['type'] == 'AutoIncrement') {
                $autoIncrementAttributes[] = $attribute;
            }
        }

        return $autoIncrementAttributes;
    }


    /**
     * @return string
     *
     * Returns a basic identity string useful for debugging output
     *
     * Examples:
     *  Customer 5eaa5a45af2f5c356d52c59c "Michael Kubler"
     *  Faq 5eb94118af2f5c07445b81e4 "How does the Mozzler Base Ident work?"
     */
    public function ident()
    {
        return \Yii::$app->t::getModelClassName($this) . " {$this->getId()} \"{$this->name}\"";
    }

    /**
     * Save And Yii::error any save Errors
     *
     * @param bool $checkPermissions
     * @return bool
     *
     * Outputs a Yii::error if the save failed.
     * NOTE: This is expected to be used in background processing scripts and the like as the checkPermissions defaults to false
     * You'll need to set it to true if using it in a client context and not an admin or task one
     */
    public function saveAndLogErrors($checkPermissions = false)
    {
        $save = $this->save(true, null, $checkPermissions);
        if (!$save) {
            \Yii::error("Error saving {$this->ident()}. Validation Error(s): " . VarDumper::export($this->getErrors()));
        }
        return $save;
    }


}
