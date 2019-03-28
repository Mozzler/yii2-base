<?php

namespace mozzler\base\views\model\auditlog\viewauditlog;

use mozzler\base\components\Tools;
use mozzler\base\models\AuditLog;
use mozzler\base\widgets\BaseWidget;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\bootstrap\Modal;

class ViewAuditLog extends BaseWidget
{

    /*
     * Example usage in the ViewModel.php
     *
      {% if widget.auditLogAttached %}
        {{ t.renderWidget("mozzler.base.views.model.auditlog.viewauditlog.ViewAuditLog",{"model": widget.model }) }}
      {% endif %}
     */

    /**
     * @return array
     */
    public function defaultConfig()
    {
        return [
            'tag' => 'div',
            'options' => [
                'class' => 'widget-audit-log-entries'
            ],
            'model' => null,
            'auditLogEntries' => [],
            'limit' => 100,
            'showModal' => true,
            'modalConfig' => [
                'size' => Modal::SIZE_LARGE,
                'header' => "Audit Log",
                // Sent to the modal widget as per https://www.yiiframework.com/extension/yiisoft/yii2-bootstrap/doc/api/2.0/yii-bootstrap-modal
                'toggleButton' => ['label' => 'Show AuditLog', 'class' => 'btn btn-primary btn-sm'],
//                'class="btn btn-primary btn-sm"'
            ],
        ];
    }

    public function code()
    {
        $config = $this->config();

        if (empty($config['model'])) {
            throw new Exception("There's no model provided to the auditLog. Try calling it and setting the model correctly");
        }
        $model = $config['model'];


        // -- Get the associated auditLogs
        $auditLogs = Tools::getModels(AuditLog::class, ['entityId' => Tools::ensureId($model->getId()), 'entityType' => $model::className()], ['limit' => $config['limit'], 'orderBy' => ['createdAt' => -1]]);
        if (!empty($auditLogs)) {
            $auditLogEntries = ArrayHelper::index($auditLogs, null, 'actionId');

            foreach ($auditLogEntries as $auditLogActionId => $auditLogSet) {
                try {
                    /**
                     * @var Int $auditLogIndex
                     * @var AuditLog $auditLog
                     */
                    foreach ($auditLogSet as $auditLogIndex => $auditLog) {
                        if (empty($auditLog)) {
                            \Yii::warning("Unexpectedly empty auditLog. \$auditLogEntries[$auditLogActionId][$auditLogIndex]");
                            continue;
                        }
                        if (isset($auditLog->previousValue)) {
                            $auditLog->previousModel = Tools::createModel($auditLog->entityType, [$auditLog->field => $auditLog->previousValue]);
                        }
                        if (isset($auditLog->newValue)) {
                            $auditLog->newModel = Tools::createModel($auditLog->entityType, [$auditLog->field => $auditLog->newValue]);
                        }
                    }
//                    // -- JSON Decode the values
//                    foreach (['newValue', 'previousValue'] as $fieldToProcess) {
//                        if (isset($auditLog[$fieldToProcess])) {
//                            $value = json_decode($auditLog[$fieldToProcess], true);
//                            // Arrays we want as JSON strings
//                            if (is_array($value)) {
//                                $value = json_encode($value, JSON_PRETTY_PRINT);
//                            }
//                            // Booleans which we want as 'true' or 'false'
//                            $auditLogEntries[$auditLogIndex][$fieldToProcess] = is_bool($value) ? json_encode($value) : (string)$value;
//                        }
//                    }
                } catch (\Throwable $exception) {
                    \Yii::warning("Unable to process the auditLog #{$auditLogActionId} " . Tools::returnExceptionAsString($exception));
                }
            }

            $config['auditLog'] = $auditLogEntries;
        } else {
            \Yii::warning("No auditLog entries found.");
        }
        return $config;
    }

}

?>