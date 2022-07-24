<?php

namespace mozzler\base\widgets\modelReport;

use mozzler\base\widgets\BaseWidget;
use yii\helpers\VarDumper;
use yii\web\View;

class ModelReportItem extends BaseWidget
{

    public function defaultConfig()
    {
        return [
            'tag' => 'div',
            'options' => [
                'class' => '',
                'id' => 'model-report-item-' // @todo: Insert the reportItem name
            ],
            'model' => null,
            'modelName' => '',
            'reportItem' => '',
            'reportItemName' => '',
            'widgetConfig' => [],
        ];
    }


    public function config($templatify = false)
    {
        $config = parent::config($templatify);
        $reportItemName = $config['reportItemName'];

//        \Yii::debug("The config is: " . VarDumper::export($config));
        $config['options']['class'] = $config['options']['class'] . ' model-report-item'; // Add model-report-item to the class
        $config['reportItem'] = $config['model']->reportItems()[$reportItemName];
        $config['modelName'] = \Yii::$app->t::getModelClassName($config['model']);

        // For creating many colours in the getColour() as per https://github.com/d3/d3-interpolate
        $view = \Yii::$app->controller->getView();
        $view->registerJsFile('https://d3js.org/d3-color.v2.min.js', ['position' => View::POS_END], 'd3-colour.v2'); // Grabbed 9th March 2021 Original Url - https://d3js.org/d3-color.v2.min.js
        $view->registerJsFile('https://d3js.org/d3-interpolate.v2.min.js', ['position' => View::POS_END], 'd3-interpolate.v2'); //Grabbed 9th March 2021 Original Url - https://d3js.org/d3-interpolate.v2.min.js

        $config['colours'] = \Yii::$app->reportsManager->colours;
        return $config;
    }


}