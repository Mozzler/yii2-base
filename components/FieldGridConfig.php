<?php

namespace mozzler\base\components;

use yii\helpers\ArrayHelper;

use Yii;
use yii\helpers\Html;

class FieldGridConfig
{

    public $config = [];

    /**
     * @return array
     */
    public function defaultConfig()
    {
        return [
            'AutoIncrement' => [],
            'Base' => [],
            'Boolean' => [
                'class' => 'kartik\grid\BooleanColumn'
            ],
            'Date' => [
                'class' => '\kartik\grid\DataColumn',
                'format' => ['date', 'php:' . Yii::$app->formatter->dateFormat]
            ],
            'DateTime' => [
                'class' => '\kartik\grid\DataColumn',
                'format' => ['date', 'php:' . Yii::$app->formatter->datetimeFormat]
            ],
            'Email' => [],
            'Integer' => [],
            'Double' => [],
            'MongoId' => [],
            'Password' => [],
            'RelateOne' => [
                'class' => \yii\grid\DataColumn::className(),
                'format' => 'html',
                'value' => function ($model, $key, $index, $column) {
                    $attribute = $column->attribute;
                    $value = $model->$attribute;
                    $modelField = $model->getModelField($column->attribute);
                    try {
                        $value = new \MongoDB\BSON\ObjectId($value);
                    } catch (\MongoDB\Driver\Exception\InvalidArgumentException $e) {
                        if ($modelField->allowUserDefined) {
                            return $value;
                        }

                        return "";
                    }

                    $relatedModel = $model->getRelated($column->attribute);
                    if (!$relatedModel) {
                        return "";
                    }
                    $searchAttribute = $relatedModel->getModelConfig('searchAttribute');

                    return Html::a($relatedModel->$searchAttribute, $relatedModel->getUrl('view'));
                }
            ],
            'RelateMany' => [],
            'Text' => [],
            'TextLarge' => [],
            'Timestamp' => [
                'class' => '\kartik\grid\DataColumn',
                'format' => ['date', 'php:' . Yii::$app->formatter->datetimeFormat]
            ],
            'SingleSelect' => [
                'class' => '\kartik\grid\EnumColumn'
            ],
            'MultiSelect' => [
                'class' => \yii\grid\DataColumn::className(),
                'format' => 'html',
                'value' => function ($model, $key, $index, $column) {
                    $attribute = $column->attribute;
                    $modelField = $model->getModelField($attribute);
                    $labels = $modelField->getOptionLabels($model->$attribute);
                    return join(", ", $labels);
                }
            ]
        ];
    }

    public function fieldFunctions()
    {
        return [
            'SingleSelect' => function ($field) {
                return [
                    'enum' => $field->options
                ];
            }
        ];
    }

    /**
     * Build config merging the defaults with any use supplied configuration
     */
    public function config()
    {
        return ArrayHelper::merge($this->defaultConfig(), $this->config);
    }

    /**
     * Build a Grid configuration for a field.
     *
     * @param $fieldType Type of field (ie: Boolean)
     * @param $customConfig Custom configuration for this field
     * @return array
     */
    public function getFieldConfig($field, $customConfig = [])
    {
        $config = $this->config();

        if (!isset($config[$field->type])) {
            // If we add new field types but don't define them as an empty array in the defaultConfig then their header doesn't appear in the Grid column
            $config[$field->type] = []; // Default to empty
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