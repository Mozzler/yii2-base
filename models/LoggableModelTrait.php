<?php
namespace mozzler\base\models;

trait LoggableModelTrait
{

    /**
     * @param $message
     * @param $type
     *
     * Expected types: 'warning', 'info', 'error'
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
            
            if ($logEntry['type'] === "error") {
                $logLines .= "#####################\n##  {$logEntry['type']}\n#####################\nDate: " . date('r') . "\n{$message}\n\"--------\n";
            } else {

                $logLines .= "{$logEntry['type']} - {$message} | " . date('r') . "\n";
            }
        }
        return $logLines;
    }

}