<?php

namespace mozzler\base\widgets\modelReport;

use mozzler\base\widgets\BaseWidget;

class ModelReportToggleButton extends BaseWidget
{
    public function defaultConfig()
    {

        return [
            'tag' => 'a',
            'options' => [
                'class' => 'btn btn-default btn-sm btn-filter model-report-toggle-button', // Hidden by default
                'id' => 'model-report-section',
                'title' => 'Show the Reports Dashboard'
            ],
            'glyphicon' => 'glyphicon glyphicon-stats',
            'model' => null,
        ];
    }

}