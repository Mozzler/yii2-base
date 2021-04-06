<?php

namespace mozzler\base\widgets\modelReport\items;

use mozzler\base\widgets\modelReport\ModelReportItem;
use yii\helpers\ArrayHelper;

class Html extends ModelReportItem
{

    /**
     * ======================
     *   Custom HTML
     * ======================
     * @param false $templatify
     * @return array
     *
     * This is static HTML. Useful for advanced layouts or some other purpose
     *
     * Example usage:
     *
     * 'rowStart' => [
     *  'widgetClass' => 'mozzler\base\widgets\modelReport\items\Html',
     *  'ignoreColumnCount' => true, // This will screw with the normal checks to ensure there's a max of col-md-12
     *  'widgetConfig' => [
     *      'html' => '<div class="row">'
     *  ],
     * ],
     */
    public function config($templatify = false)
    {
        $config = parent::config($templatify);

        // ----------------------------------------------------------------------
        //   Custom HTML (as the html field in the widgetConfig)
        // ----------------------------------------------------------------------
        $config['html'] = ArrayHelper::getValue($config, 'reportItem.widgetConfig.html', '');
        return $config;
    }

}