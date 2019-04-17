<?php
namespace mozzler\base\widgets\yii;

use yii\grid\DataColumn;
use yii\helpers\ArrayHelper;

/**
 * A Grid column wrapper designed to render an existing widget
 * as a grid column widget.
 * 
 * For example, rendering a cell using the mozzler\base\widgets\model\view\LinkField
 */
class GridWidgetColumn extends DataColumn
{

    /**
     * Configuration for the widget to be rendered in the cell.
     * 
     * eg:
     * ```
     *  [
     *      'class' => 'example-class'
     *  ]
     * ```
     */
    public $widgetConfig = [];

    /**
     * Class of the widget to render.
     * 
     * eg: `mozzler\base\widgets\model\view\LinkField`
     */
    public $widgetClass = '';

    public function init() {
        parent::init();

        if (!$this->content) {
            $this->content = function($model, $key, $index, $column) {
                $widgetConfig = ArrayHelper::merge($this->widgetConfig, [
                    'model' => $model,
                    'attribute' => $column->attribute
                ]);

                return \Yii::$app->t::renderWidget($this->widgetClass, $widgetConfig);
            };
        }
    }

}