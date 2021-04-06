<?php

namespace mozzler\base\widgets\modelReport\items;

use mozzler\base\widgets\modelReport\ModelReportItem;
use mozzler\base\exceptions\BaseException;
use yii\helpers\ArrayHelper;

class Table extends ModelReportItem
{

    /**
     * ======================
     *   Table Display
     * ======================
     * @param false $templatify
     * @return array
     */
    public function config($templatify = false)
    {
        $config = parent::config($templatify);

        $config['options']['class'] = $config['options']['class'] . ' model-report-item-table';

        // ----------------------------------------------------------------------
        //   Table
        // ----------------------------------------------------------------------
        $tableId = strtolower(htmlentities(\Yii::$app->t::getModelClassName($config['model']) . $config['reportItemName'] . '-' . $config['id']));
        $config['tableId'] = $tableId;
        $this->outputJsData([
            'tableId' => $tableId,
            'reportItem' => ArrayHelper::getValue($config, 'reportItem.widgetConfig', []),
            'modelName' => $config['modelName'],
            'title' => ArrayHelper::getValue($config, 'reportItem.title', null),
            'reportItemName' => $config['reportItemName'],
            'apiEndpoint' => \Yii::$app->reportsManager->apiEndpoint,
        ]);
        return $config;
    }


    public function processConfigAndDataResponse($config, $reportItem)
    {
        $data = $config['data'];
        $columns = ArrayHelper::getValue($reportItem, 'data.columns');

        $tableClasses = ArrayHelper::getValue($reportItem, 'widgetConfig.tableClasses', 'table');
        $tableCaption = ArrayHelper::getValue($reportItem, 'widgetConfig.caption');

        // Init
        $tableHtml = "<table class='{$tableClasses}'>\n";

        // -- Caption (optional)
        if ($tableCaption) {
            $tableHtml .= "<caption>$tableCaption</caption>\n";
        }


        // Add the header row
        if (!empty($columns)) {
            $tableHtml .= '<thead><tr>';
            foreach ($columns as $columnName => $column) {
                $header = ArrayHelper::getValue($column, 'header');
                $headerClass = ArrayHelper::getValue($column, 'headerClass');
                $tableHtml .= "<th class='$headerClass'>$header</th>";
            }
            $tableHtml .= '</tr></thead>';


            foreach ($data as $rowIndex => $row) {
                // The row could be an Aggregation entry or a model
                $tableHtml .= '<tr>';
                foreach ($columns as $columnName => $column) {
                    if (!isset($column['value'])) {
                        throw new BaseException("Column $columnName entry needs a value entry for " . ArrayHelper::getValue($reportItem, 'title'));
                    }

                    $columnClass = ArrayHelper::getValue($column, 'columnClass', '');
                    $value = '';
                    if (is_string($column['value'])) {
                        $value = ArrayHelper::getValue($row, $column['value']);
                    }
                    if (is_callable($column['value'])) {
                        $value = $column['value']($row, $rowIndex);
                    }
                    $tableHtml .= "<td class='$columnClass'>$value</td>";
                }
            }

        }


        $tableHtml .= '</table>';
        $config['data'] = $tableHtml;

        return $config;

    }

}