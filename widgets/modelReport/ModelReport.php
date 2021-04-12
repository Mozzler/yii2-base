<?php

namespace mozzler\base\widgets\modelReport;

use mozzler\base\exceptions\BaseException;
use mozzler\base\widgets\BaseWidget;

class ModelReport extends BaseWidget
{

    public function defaultConfig()
    {

        return [
            'tag' => 'div',
            'options' => [
                'class' => 'model-report-section hidden', // Hidden by default
                'id' => 'model-report-section'
            ],
            'model' => null,
            'configOptions' => [],
            'modelReports' => [],
            'colourGradient' => null, // Will default to 'colours'
        ];
    }


    public function config($templatify = false)
    {
        $config = parent::config($templatify);

        $model = $config['model'];
        if (empty($model)) {
            throw new BaseException('The model must be specified');
        }
        $config['modelClassName'] = \Yii::$app->t::getModelClassName($model);
        $reportItems = $model->reportItems();
        if (empty($reportItems)) {
            \Yii::warning("No report items to display for the model {$config['modelClassName']}");
            return $config;
        }

        if (!method_exists($model, 'reportItemsLayout')) {
            $reportItemsLayout = \Yii::$app->reportsManager->getDefaultReportItemsLayout($reportItems, $model);
        } else {
            $reportItemsLayout = $model->reportItemsLayout();
        }


        $config['reportItemsLayoutWidgetMode'] = \Yii::$app->reportsManager->convertReportItemsLayoutToWidgetMode($reportItemsLayout, $reportItems, $model);


        $this->outputJsData([
            'reportItemColours' => \Yii::$app->reportsManager->getColourGradients($config['colourGradient']),
        ]);
        return $config;
    }

}