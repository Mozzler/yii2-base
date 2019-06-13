<?php

namespace mozzler\base\models\behaviors;

use yii\base\Behavior;
use yii\base\Event;
use yii\behaviors\AttributesBehavior;
use yii\db\BaseActiveRecord;

/**
 * AuditLog Behaviour for logging all changes to an entity
 * and who made those changes
 */
class GarbageCollectionBehaviour extends Behavior
{

    /**
     *
     * @var int $gcProbability the probability (parts per million) that garbage collection (GC) should be performed
     * when running the cron.
     * Defaults to 10000, meaning 1% chance.
     * This number should be between 0 and 1000000. A value 0 meaning no GC will be performed at all.
     */
    public $gcProbability = 10000;

    /** @var int $gcAgeDays */
    public $gcAgeDays = 30;

    /** @var bool $force */
    public $force = false;


    public function events()
    {
        return [
            BaseActiveRecord::EVENT_AFTER_UPDATE => 'gc',
            BaseActiveRecord::EVENT_AFTER_INSERT => 'gc',
        ];
    }


    /**
     * Garbage Collection
     *
     * @param Event $event
     * @return bool
     * @throws \yii\base\InvalidConfigException
     *
     * Deletes all records that are older than self::$gcAgeDays
     */
    public function gc($event)
    {
        $model = $this->owner;
        if ($this->force || mt_rand(0, 1000000) < $this->gcProbability) {
            // 1% of the time delete all records that are older than 30 days

            \Yii::info("Triggering the garbage collection run. Removing " . self::class . " items older than {$this->gcAgeDays} days ago");
            /** @var \mozzler\base\models\Model $model */
            $unixTimeOfGC = time() - ($this->gcAgeDays * 86400);
            \Yii::$app->rbac->ignoreCollection($model::collectionName()); // Get around any RBAC issues
            $model->deleteAll(['<', 'createdAt', $unixTimeOfGC]);
            \Yii::$app->rbac->dontIgnoreCollection($model::collectionName());
            return true;
        }
        \Yii::debug("NOT Triggering the garbage collection run. Removing " . self::class . " items older than {$this->gcAgeDays} days ago");
        return false;
    }
}
