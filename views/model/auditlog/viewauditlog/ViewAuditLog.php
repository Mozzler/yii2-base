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
     * Example usage in the ViewModel.twig
     *
      {% if widget.auditLogAttached %}
        {{ t.renderWidget("mozzler.base.views.model.auditlog.viewauditlog.ViewAuditLog",{"model": widget.model }) }}
      {% endif %}

     * Note: You also need to add the button to trigger this as it's been disabled by default and the ViewModel panel creates the button instead
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
            'format' => \Yii::$app->formatter->datetimeFormat,
            'timeZone' => \Yii::$app->formatter->timeZone,
            'modalConfig' => [
                // Set the modal widget config. https://www.yiiframework.com/extension/yiisoft/yii2-bootstrap/doc/api/2.0/yii-bootstrap-modal
                'size' => Modal::SIZE_LARGE,
                'header' => "Audit Log",
                'toggleButton' => false, // This should be shown in the widgets/model/ViewModel.php panelConfig heading content
            ],
        ];
    }

    public function code($templatify = false)
    {
        $config = $this->config();

        if (empty($config['model'])) {
            throw new Exception("There's no model provided to the auditLog. Try calling it and setting the model correctly");
        }
        $model = $config['model'];


        // -- Get the associated auditLogs
        $auditLogs = \Yii::$app->t::getModels(AuditLog::class, ['entityId' => $model->_id, 'entityType' => $model::className()], ['limit' => $config['limit'], 'orderBy' => ['createdAt' => -1]]);
        if (!empty($auditLogs)) {
            $auditLogEntries = ArrayHelper::index($auditLogs, null, 'actionId');

            foreach ($auditLogEntries as $auditLogActionId => $auditLogSet) {
                try {
                    /**
                     * @var AuditLog $auditLog
                     */
                    foreach ($auditLogSet as $auditLogIndex => $auditLog) {
                        if (empty($auditLog)) {
                            \Yii::warning("Unexpectedly empty auditLog. \$auditLogEntries[$auditLogActionId][$auditLogIndex]");
                            continue;
                        }
                        if (isset($auditLog->previousValue)) {
                            $auditLog->previousModel = \Yii::$app->t::createModel($auditLog->entityType, [$auditLog->field => $auditLog->previousValue]);
                        }
                        if (isset($auditLog->newValue)) {
                            $auditLog->newModel = \Yii::$app->t::createModel($auditLog->entityType, [$auditLog->field => $auditLog->newValue]);
                        }
                    }
                } catch (\Throwable $exception) {
                    \Yii::warning("Unable to process the auditLog #{$auditLogActionId} " . \Yii::$app->t::returnExceptionAsString($exception));
                }
            }

            $config['auditLog'] = $auditLogEntries;
        } else {
            \Yii::warning("No auditLog entries found.");
        }
        return $config;
    }

}
