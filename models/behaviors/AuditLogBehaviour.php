<?php
namespace mozzler\base\models\behaviors;

use MongoDB\BSON\ObjectId;
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

    public $actionId = '';
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();


        $this->actionId = new ObjectId(); // This should be fairly unique for this action
        $attributes = [];

        // We need to save the auditLog :
        // - after the Insert we know the modelId
        // - before the Update so we know the previous value
        //- Before the Delete so we know the modelId
        foreach ($this->attributes() as $attribute) {
            $attributes[$attribute] = [
                BaseActiveRecord::EVENT_AFTER_INSERT=> [$this, 'saveAuditLog'],
                BaseActiveRecord::EVENT_BEFORE_UPDATE => [$this, 'saveAuditLog'],
                BaseActiveRecord::EVENT_BEFORE_DELETE => [$this, 'saveAuditLog']
            ];
        }
    }

    /**
     */
    protected function saveAuditLog($event, $attribute)
    {


        /** @var Model $model */
        $model = $this->owner;

        $auditLogData = [
            'newValue' => $model->$attribute,
            'field' => $attribute,
            'entityId' => $model->getId(),
            'entityType' => get_class($model),
            'action' => AuditLog::ACTION_UPDATE // @todo: Work this out
        ];


    // @todo: Work out the EVENT and if EVENT_BEFORE_UPDATE then get the previous value
        // locate the previous inserted autoincrement value for this attribute
//        $models = \Yii::$app->t::getModels($model::className(), [], [
//            'sort' => [
//                $attribute => -1
//            ],
//            'limit' => 1,
//            'checkPermissions' => false
//        ]);
//
        return $this->owner->$attribute; // Return the original attribute
    }
}
