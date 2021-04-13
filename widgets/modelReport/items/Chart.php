<?php

namespace mozzler\base\widgets\modelReport\items;

use mozzler\base\widgets\modelReport\ModelReportItem;
use yii\helpers\ArrayHelper;
use yii\web\View;

class Chart extends ModelReportItem
{

    // ChartJs chart
    public function config($templatify = false)
    {
        $config = parent::config($templatify);

        $config['options']['class'] = $config['options']['class'] . ' model-report-item-chart'; // Ensure the chart class is added
        // ----------------------------------------------------------------------
        //   Chart.js
        // ----------------------------------------------------------------------
        $view = \Yii::$app->controller->getView();

        $canvasId = strtolower(htmlentities(\Yii::$app->t::getModelClassName($config['model']) . $config['reportItemName'] . '-' . $config['id']));
        $config['canvasId'] = $canvasId;

        // -- Register the ChartJS files
        // NB: As of 24th Feb 2021 v2.9.4 is the latest. v3 is being developed but still in beta, so using the latest v2 https://cdnjs.com/libraries/Chart.js
        $view->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js', ['position' => View::POS_END], 'chartjs.v2'); // The very last v2 version before v3 released. https://cdn.jsdelivr.net/npm/chart.js@2.9.4 or Alternative URL https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js
        $this->outputJsData([
            'canvasId' => $canvasId,
            'reportItem' => $config['reportItem']['widgetConfig'],
            'modelName' => $config['modelName'],
            'title' => ArrayHelper::getValue($config, 'reportItem.title', null),
            'reportItemName' => $config['reportItemName'],
            'apiEndpoint' => \Yii::$app->reportsManager->apiEndpoint,
        ]);
        return $config;
    }

}