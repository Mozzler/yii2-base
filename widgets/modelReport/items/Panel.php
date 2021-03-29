<?php

namespace mozzler\base\widgets\modelReport\items;

use mozzler\base\widgets\modelReport\ModelReportItem;
use yii\helpers\ArrayHelper;

class Panel extends ModelReportItem
{

    /**
     * ======================
     *   Panel Display
     * ======================
     * @param false $templatify
     * @return array
     */
    public function config($templatify = false)
    {
        $config = parent::config($templatify);

        $config['options']['class'] = $config['options']['class'] . ' model-report-item-panel';

        // ----------------------------------------------------------------------
        //   PANEL
        // ----------------------------------------------------------------------
        $panelId = strtolower(htmlentities(\Yii::$app->t::getModelClassName($config['model']) . $config['reportItemName'] . '-' . $config['id']));
        $config['panelId'] = $panelId;
        $this->outputJsData([
            'panelId' => $panelId,
            'reportItem' => ArrayHelper::getValue($config, 'reportItem.widgetConfig', []),
            'modelName' => $config['modelName'],
            'title' => ArrayHelper::getValue($config, 'reportItem.title', null),
            'reportItemName' => $config['reportItemName'],
            'apiEndpoint' => \Yii::$app->reportsManager->apiEndpoint,
        ]);
        return $config;
    }

}