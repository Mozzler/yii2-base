<?php

namespace mozzler\base\actions;

use mozzler\base\actions\BaseModelAction as BaseAction;
use mozzler\base\exceptions\BaseException;
use mozzler\base\models\Model;

/**
 * Accept the following query parameters:
 *  - model name (so can generate an instance of the model to call reportItems() to get config) ie: "Deal"
 *  - report item name (ie: report-item-1)
 *
 * Returns JSON data response that the widget will use to inject into the report item
 * Will call a "generateData" method on ReportsManager that does all the work
 *
 * All the widgets (charts, panel etc.) will all point to this endpoint /report-item?_model=Deal&_item=reportItem1&[search query items]
 *
 */
class ReportItemAction extends BaseAction
{

    public $resultScenario = Model::SCENARIO_VIEW_API;

    public function run()
    {


        $modelType = \Yii::$app->request->get('model'); // e.g Deal or TestDrive
        $reportItemName = \Yii::$app->request->get('reportItem'); // e.g  'test-drives-by-user'
        if (empty($modelType)) {
            throw new BaseException("Model required");
        }
        if (empty($reportItemName)) {
            throw new BaseException("Report Item name required");
        }

        /** @var Model $model */
        $model = \Yii::createObject(("app\models\\{$modelType}"));
        if (!method_exists($model, 'reportItems')) {
            throw new BaseException("$modelType is invalid and doesn't have any report items");
        }
        $rbacFilter = \Yii::$app->rbac->canAccessAction($this);
        return \Yii::$app->reportsManager->returnDataAndConfig($model, $reportItemName, $rbacFilter);
    }

}
