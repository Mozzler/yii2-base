<?php

namespace mozzler\base\widgets\viewauditlog;

use mozzler\base\components\Tools;
use mozzler\base\models\AuditLog;
use mozzler\base\widgets\BaseWidget;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

class ViewAuditLog extends BaseWidget
{

    /*
     * Example usage in the ViewModel.php
     *
      {% if widget.auditLogAttached %}
        {{ t.renderWidget("mozzler.base.widgets.viewauditlog.ViewAuditLog",{"model": widget.model }) }}
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
            'limit' => 100
        ];
    }

    public function code()
    {
        $config = $this->config();

        if (empty($config['model'])) {
            throw new Exception("There's no model provided to the auditLog. Try calling it using {{ t.renderWidget(\"mozzler.base.widgets.viewauditlog.ViewAuditLog\",{\"model\": widget.model }) }}");
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
                    foreach($auditLogSet as $auditLogIndex => $auditLog) {
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