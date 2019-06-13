<?php

namespace mozzler\base\models\behaviors;

use yii\behaviors\AttributesBehavior;

/**
 * AuditLog Behaviour for logging all changes to an entity
 * and who made those changes
 */
class GarbageCollectionBehaviour extends AttributesBehavior
{

    /**
     * @var int the probability (parts per million) that garbage collection (GC) should be performed
     * when running the cron.
     * Defaults to 10000, meaning 1% chance.
     * This number should be between 0 and 1000000. A value 0 meaning no GC will be performed at all.
     */
    public static $gcProbability = 10000;

    public static $gcAgeDays = 30;


    /**
     * Garbage Collection
     *
     * @param bool $force
     * @return bool
     * @throws \yii\base\InvalidConfigException
     *
     * Deletes all records that are older than self::$gcAgeDays
     */
    public static function gc($force = false)
    {
        if ($force || mt_rand(0, 1000000) < self::$gcProbability) {
            // 1% of the time delete all records that are older than 30 days

            /** @var \mozzler\base\models\Model $model */
            $model = \Yii::createObject(self::class);
            $unixTimeOfGC = time() - (self::$gcAgeDays * 86400);
            \Yii::$app->rbac->ignoreCollection(self::collectionName()); // Get around any RBAC issues
            $model->deleteAll(['<', 'createdAt', $unixTimeOfGC]);
            \Yii::$app->rbac->dontIgnoreCollection(self::collectionName());
            return true;
        }
        return false;
    }
}
