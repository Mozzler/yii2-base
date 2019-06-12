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

            $this->auditLogActionId = (string)new ObjectId(); // This should be fairly unique for this action, allowing grouping of updates
            $attributes = [];

            // We need to save the auditLog :
            // - After the Insert so we know the modelId
            // - After the Update so we know the previous and validated updated value 


            if (!empty($this->auditLogAttributes)) {
                foreach ($this->auditLogAttributes as $attribute) {
                    $attributes[$attribute] = [
                        BaseActiveRecord::EVENT_AFTER_INSERT => [$this, 'saveAuditLog'],
                        BaseActiveRecord::EVENT_AFTER_UPDATE => [$this, 'saveAuditLog']
                    ];
                }
                $this->attributes = $attributes;
            } else {
                \Yii::warning("The AuditLogBehaviour can't find the \$this->auditLogAttributes to be applied to. Ensure you've assigned some attributes to be applied");
            }
        } catch (\Throwable $exception) {
            \Yii::error("The AuditLogBehaviour Init errored with: " . Tools::returnExceptionAsString($exception));
        }
    }

    /**
     * Save Audit Log
     *
     * The main method which gets executed for each attribute
     *
     * @param $event
     * @param $attribute
     * @return mixed
     */
    protected function saveAuditLog($event, $attribute)
    {
        try {

            /** @var Model $model */
            $model = $this->owner;

            if (!isset($model->$attribute) && (empty($event->changedAttributes) || empty($event->changedAttributes[$attribute]))) {
                // -- Don't try to log empty fields if they were empty before and still are
                return $model->$attribute;
            }

            $action = $event->name; // Init
            switch ($event->name) {
                case BaseActiveRecord::EVENT_AFTER_INSERT:
                    $action = AuditLog::ACTION_INSERT;
                    break;
                case BaseActiveRecord::EVENT_AFTER_UPDATE:
                    $action = AuditLog::ACTION_UPDATE;
                    break;
            }

            $auditLogData = [
                'newValue' => empty($model->$attribute) ? '(empty)' : $model->$attribute,
                'field' => $attribute,
                'entityId' => Tools::ensureId($model->getId()),
                'entityType' => get_class($model),
                'action' => $action,
                'actionId' => $this->auditLogActionId,
            ];

            // Example $event->changedAttributes = {"value_":"68","updatedAt":1553749836,"updatedUserId":{"$oid":"5c4e56ef52c0ce0c815f5232"}}
            if (!empty($event->changedAttributes) && isset($event->changedAttributes[$attribute])) {
                $auditLogData['previousValue'] = empty($event->changedAttributes[$attribute]) ? '(empty)' : $event->changedAttributes[$attribute];
            } else if (AuditLog::ACTION_UPDATE === $action) {
                return $this->owner->$attribute; // The field hasn't changed, so return the original attribute and don't save this
            }

            $auditLog = Tools::createModel(AuditLog::class, $auditLogData);
            $auditLogSaved = $auditLog->save(true, null, false);
            if (!$auditLogSaved) {
                \Yii::error("AuditLogBehaviour - Error saving the AuditLog :\n" . print_r($auditLog->getErrors(), true));
            }

        } catch (\Throwable $exception) {
            \Yii::error("The AuditLogBehaviour saveAuditLog() threw an exception with: " . Tools::returnExceptionAsString($exception));
        }
        return $this->owner->$attribute; // Return the original attribute
    }
}
