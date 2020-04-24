<?php

namespace mozzler\base\models\traits;

trait LoggableModelTrait
{

    // -- Traits can't have const so using vars and adding the _ for reduced namespace collision chance
    public $_LOG_TYPE_WARNING = 'warning';
    public $_LOG_TYPE_INFO = 'info';
    public $_LOG_TYPE_ERROR = 'error';


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
        $this->log = $log;
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