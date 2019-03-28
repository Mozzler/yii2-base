<?php

namespace mozzler\base\models\behaviors;

use MongoDB\BSON\ObjectId;
use mozzler\base\components\Tools;
use mozzler\base\models\AuditLog;
use mozzler\base\models\Model;
use yii\db\BaseActiveRecord;
use yii\behaviors\AttributesBehavior;

/**
 * AuditLog Behaviour for logging all changes to an entity
 * and who made those changes
 */
class AuditLogBehaviour extends AttributesBehavior
{

    public $auditLogActionId = '';

    public $auditLogAttributes;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        try {

            $this->auditLogActionId = (string)new ObjectId(); // This should be fairly unique for this action
            $attributes = [];

            $auditLogAttributes = [];
            if (!empty($this->auditLogAttributes)) {
                $auditLogAttributes = $this->auditLogAttributes;
            } else if (!empty($this->owner) && method_exists($this->owner, 'attributes')) {
                $auditLogAttributes = $this->owner->attributes();
            }

            // We need to save the auditLog :
            // - After the Insert so we know the modelId
            // - After the Update so we know the previous and validated updated value
            // - After the Delete so we know it was properly deleted


            if (!empty($auditLogAttributes)) {
                foreach ($auditLogAttributes as $attribute) {
                    $attributes[$attribute] = [
                        BaseActiveRecord::EVENT_AFTER_INSERT => [$this, 'saveAuditLog'],
                        BaseActiveRecord::EVENT_AFTER_UPDATE => [$this, 'saveAuditLog'],
                        BaseActiveRecord::EVENT_AFTER_DELETE => [$this, 'saveAuditLog']
                    ];
                }
                $this->attributes = $attributes;
            } else {
                \Yii::warning("The AuditLogBehaviour can't find the auditLogAttributes to be applied to. Ensure you've used it correctly");
            }
        } catch (\Throwable $exception) {
            \Yii::error("The AuditLogBehaviour Init errored with: " . Tools::returnExceptionAsString($exception));
        }
    }

    /**
     */
    protected function saveAuditLog($event, $attribute)
    {
        try {

            /** @var Model $model */
            $model = $this->owner;

            $action = $event->name; // Init
            switch ($event->name) {
                case BaseActiveRecord::EVENT_AFTER_INSERT:
                    $action = AuditLog::ACTION_INSERT;
                    break;
                case BaseActiveRecord::EVENT_AFTER_UPDATE:
                    $action = AuditLog::ACTION_UPDATE;
                    break;
                case BaseActiveRecord::EVENT_AFTER_DELETE:
                    $action = AuditLog::ACTION_DELETE;
                    break;
            }

            $auditLogData = [
                'newValue' => $model->$attribute,
                'field' => $attribute,
                'entityId' => Tools::ensureId($model->getId()),
                'entityType' => get_class($model),
                'action' => $action,
                'actionId' => $this->auditLogActionId,
            ];


            if (!empty($event->changedAttributes) && isset($event->changedAttributes[$attribute])) {
                $auditLogData['previousValue'] = $event->changedAttributes[$attribute];
                if (json_encode($auditLogData['previousValue']) === json_encode($auditLogData['newValue']) && AuditLog::ACTION_INSERT !== $action) {
                    return $this->owner->$attribute; // The field hasn't changed, so return the original attribute and don't save this
                }
            }
//                // Locate the previous value for this attribute (used if BEFORE_UPDATE... But you shouldn't be using that)
//                $previousModel = Tools::getModel($model::className(), ['_id' => Tools::ensureId($model->getId())], false);
//                if (!empty($previousModel)) {
//                    $auditLogData['previousValue'] = $previousModel->$attribute;
//
//                    if (json_encode($auditLogData['previousValue']) === json_encode($auditLogData['newValue'])) {
//                        return $this->owner->$attribute; // The field hasn't changed, so return the original attribute and don't save this
//                    }
//                }

            $auditLog = Tools::createModel(AuditLog::class, $auditLogData);
            $auditLogSaved = $auditLog->save(true, null, false);
            if (!$auditLogSaved) {
                \Yii::error("auditLog save error:\n" . print_r($auditLog->getErrors(), true));
            }

        } catch (\Throwable $exception) {
            \Yii::error("The AuditLogBehaviour saveAuditLog() threw an exception with: " . Tools::returnExceptionAsString($exception));
        }
        return $this->owner->$attribute; // Return the original attribute
    }
}
