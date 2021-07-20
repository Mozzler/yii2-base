<?php

namespace mozzler\base\widgets\model\view;

use yii\helpers\ArrayHelper;

/**
 * Class FormattedField
 *
 * @package mozzler\base\widgets\model\view
 *
 * A general formatted field
 *
 * Used for viewing data in a specific way.
 *
 * By default the formatterMethod is set to ntext (newlines replaced with <br /> )
 * However there's plenty of other options the Yii2 \yii\i18n\Formatter supports including
 * 'date', 'percent', 'email', 'boolean', 'date', 'time', 'timestamp', 'relativeTime', 'duration',
 * 'integer', 'decimal', 'percent', 'scientific', 'currency', 'size' (human readable filesize), 'shortSize'
 * 'raw', 'text', 'ntext', 'paragraphs', 'html', 'image', 'url', 'boolean'
 *
 * More info:
 * https://www.yiiframework.com/doc/guide/2.0/en/output-formatting
 * https://www.yiiframework.com/doc/api/2.0/yii-i18n-formatter
 *
 * The preContent and postContent are for adding anything around the value that might be needed. postContent is especially useful for a units signifier, like 'Km/h'
 *
 * The valueTransformFunction is a way of changing the value to make it more useful to the formatter.
 * Or you can set the formatterMethod to raw and use the valueTransformFunction to output something custom.
 * The original $value, $attribute, $model and full $config are provided for access
 *
 *
 * Example usage:
 *
 *
 * File: Models/Timer.php
 *
 * public function modelFields() {
 * return ArrayHelper::merge(parent::modelFields(), [
 *
 * 'elapsedTime' => [
 *  'label' => 'Elapsed Time',
 *  'type' => 'Integer', // Time in seconds
 *  'widgets' => [
 *  'view' => [
 *    'class' => 'mozzler\base\widgets\model\view\FormattedField',
 *    'formatterMethod' => 'percent',
 *    'formatterOptions' => [1] // Number of decimals
 *  ],], ],
 *
 * 'elapsedTime' => [
 *  'label' => 'Elapsed Time',
 *  'type' => 'Double', // Percentage (up to 100)
 *  'widgets' => [
 *  'view' => [
 *    'class' => 'mozzler\base\widgets\model\view\FormattedField',
 *    'formatterMethod' => 'percent',
 *    'formatterOptions' => [1] // Number of decimals
 * ],], ],
 *
 * ]); }
 */
class FormattedField extends BaseField
{
    public function defaultConfig()
    {
        return \yii\helpers\ArrayHelper::merge(parent::defaultConfig(), [
            "tag" => "span",
            "formatterMethod" => 'ntext', // View https://www.yiiframework.com/doc/guide/2.0/en/output-formatting for more info, this is using the array method e.g 'percent' instead of 'asPercent'
            "formatterOptions" => [], // e.g 2 for percent is the number of decimals
            "preContent" => '', // e.g $ (although use the Currently Field for dollars instead)
            "postContent" => '', // e.g '%' or ' Km/h'
            // A user definable function which can be used to pre-process the value field before being sent to the formatter
            "valueTransformFunction" => function ($value, $attribute, $model = null, $config = null) {
                return $value;
            },
            "options" => [
                "class" => "",
            ],
            "model" => null,
            "attribute" => null
        ]);
    }

    public function config($templatify = true)
    {
        $config = parent::config(true);

        if (!empty($config['attribute']) && !empty($config['model'])) {
            $attributeValue = ArrayHelper::getValue($config, 'model.' . $config['attribute']);
            $attributeValue = $config['valueTransformFunction']($attributeValue, $config['attribute'], $config['model'], $config); // Allow for custom value transformations
            $config['formattedValue'] = $config['preContent'] . \Yii::$app->formatter->format($attributeValue, ArrayHelper::merge([$config['formatterMethod']], $config['formatterOptions'])) . $config['postContent'];
//            \Yii::debug("The FormattedField formatted Value is " . json_encode($config['formattedValue']));
        }
        return $config;
    }

}
