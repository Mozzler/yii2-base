<?php

namespace mozzler\base\models\traits;


use yii\helpers\ArrayHelper;

trait LoggableModelTrait
{

    // -- Traits can't have const so using vars and adding the _ for reduced namespace collision chance
    public $_LOG_TYPE_WARNING = 'warning';
    public $_LOG_TYPE_INFO = 'info';
    public $_LOG_TYPE_DEBUG = 'debug';
    public $_LOG_TYPE_ERROR = 'error';

//    /**
//     * @var int|null
//     */
//    public $_MAX_LOG_ENTRIES = 0; // If set, then we also cull the array to be at most this many entries.


    /**
     * @param $message
     * @param $type
     *
     * Expected types: 'warning', 'info', 'error' e.g $this->_LOG_TYPE_ERROR
     */
    public function addLog($message, $type = 'info')
    {
        if (empty($this->log)) {
            $this->log = [];
        }
        $log = $this->log; // Trying to avoid the "Indirect modification of overloaded property has no effect" issue as per https://stackoverflow.com/questions/10454779/php-indirect-modification-of-overloaded-property
        $log[] = [
            'timestamp' => time(),
            'message' => $message,
            'type' => $type
        ];

        // -- Allow trimming the log entries to a maximum size
        if (isset($this->_MAX_LOG_ENTRIES) && $this->_MAX_LOG_ENTRIES > 0 && count($log) > $this->_MAX_LOG_ENTRIES) {
            // -- Get the X last most entries
            $log = $this->_trimLog($log, $this->_MAX_LOG_ENTRIES); // By default we remove the debug and info entries before removing the warning and error ones and trim the oldest (earlier in the array) entries, but you can replace this with your own implementation
        }
        $this->log = $log;

        if ($type === $this->_LOG_TYPE_ERROR) {
            \Yii::error($message);
        } elseif ($type === $this->_LOG_TYPE_WARNING) {
            \Yii::warning($message);
        } else {
            \Yii::info($message);
        }
    }

//    /**
//     * @param $log array
//     * @param $maxLogSize int
//     *
//     * This trim log function will simply remove older entries (because of the - (negative) to the max log size on the array_slice
//     */
//    public function _trimLog($log, $maxLogSize)
//    {
//        return array_slice($log, -$maxLogSize); // If offset is negative, the sequence will start that far from the end of the array.
//    }

    /**
     * @param $log array
     * @param $maxLogSize int
     * @return array
     *
     * This trim log function is defined separately in case you want to do more work to selectively remove certain, more noisy log entries whilst keeping more useful ones
     * Or you want to instead use the more naive approach of just trimming the earliest (or latest or whatever you want)
     */
    public function _trimLog($log, $maxLogSize)
    {
        $overByCount = count($log) - $maxLogSize;
        if ($overByCount > 0) {
            // -- First, we clear out DEBUG entries (which are the access logs, like "Someone viewed the Client Payment Page")
            $log = array_filter($log, function ($logEntry) use (&$overByCount) {
                if ($overByCount > 0 && ArrayHelper::getValue($logEntry, 'type') === $this->_LOG_TYPE_DEBUG) {
                    $overByCount--;
                    return false;
                }
                return true;
            });
            $overByCount = count($log) - $maxLogSize; // Recount probably not needed?
            if ($overByCount > 0) {
                // Also remove whatever the oldest info entries are as required (hopefully we don't need to do this)
                $log = array_filter($log, function ($logEntry) use (&$overByCount) {
                    if ($overByCount > 0 && ArrayHelper::getValue($logEntry, 'type') === $this->_LOG_TYPE_INFO) {
                        $overByCount--;
                        return false;
                    }
                    return true;
                });
            }
        }
        // -- Then we remove any other entries, from the top, leaving the most recent at the bottom
        return array_slice($log, -$maxLogSize); // If offset is negative, the sequence will start that far from the end of the array.
    }

    public function returnLogLines()
    {
        $logLines = '';
        if (empty($this->log)) {
            return $logLines;
        }
        foreach ($this->log as $logIndex => $logEntry) {
            if (is_string($logEntry['message'])) {
                $message = $logEntry['message'];
            } else if (is_array($logEntry['message'])) {
                $message = print_r($logEntry['message'], true); // Sometimes an array given
            } else {
                $message = json_encode($logEntry['message']); // Sometimes a boolean or something else
            }

            if ($logEntry['type'] === $this->_LOG_TYPE_ERROR) {
                $logLines .= "#####################\n##  {$logEntry['type']}\n#####################\nDate: " . date('r') . "\n{$message}\n\"--------\n";
            } else {

                $logLines .= "{$logEntry['type']} - {$message} | " . date('r') . "\n";
            }
        }
        return $logLines;
    }

    public function returnLogSize()
    {

        return (is_array($this->log) ? count($this->log) : 0) . ':' . number_format(strlen(json_encode($this->log)), 0);
    }

}