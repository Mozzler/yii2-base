<?php

namespace mozzler\base\exceptions;

use Throwable;
use Yii;

/**
 * Class BaseException
 * @package mozzler\base\exceptions
 *
 * This is used by the API to generate various exceptions
 * and have the default message and code added from the params
 *
 * It's expected you'll be logging Yii:error exceptions and
 */
class BaseException extends \Exception
{
    public $defaultMessage = 'Unknown system error';
    public $defaultCode = 502;

    public $name = 'BaseException';
    public $statusCode;
    public $systemLogInfo;

    /**
     * BaseException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|\Exception|null $previous
     * @param $systemLogInfo array
     */
    public function __construct($message = '', $code = 0, \Throwable $previous = null, $systemLogInfo = null)
    {
        $this->setVars($message, $code, $systemLogInfo);
        parent::__construct($this->message, $this->code, $previous); // When based off the HttpException we are using $code as the HTTP Status
    }

    public function setVars($message, $code, $systemLogInfo)
    {
        if (!empty($code)) {
            $this->code = $code;
        } else {
            $this->code = $this->defaultCode;
        }
        $this->statusCode = $this->code; // Used by the HTTP

        if (!empty($message)) {
            $this->message = $message;
        } else {
            $this->message = $this->defaultMessage;
        }

        if (!empty($systemLogInfo)) {
            $this->systemLogInfo = $systemLogInfo;
        }
    }

    /**
     * To System Log
     *
     * This is used by the System Log Target and is output to messageData.ContextualInfo if provided
     * The aim is to provide more useful contextual information to help developers identify the underlying issue.
     *
     * It's expected this function will be replaced with more exception specific ones as appropriate when you extend from this.
     *
     * e.g If the exception is a Guzzle HTTP Timeout being rethrown, then the systemLogInfo might be a Guzzle object
     * and the toSystemLog() can convert the Guzzle object into an array or string that's more useful.
     *
     * You can also extend from a different base Exception but declare a toSystemLog method as needed.
     *
     * @return array|string|null
     */
    public function toSystemLog()
    {
        if (!empty($this->systemLogInfo)) {

            // Remove and $ dollar signs from the start of the arrays as they cause MongoDB to error! e.g
            // 	Unable to send log via mozzler\base\log\SystemLogTarget: Exception 'MongoDB\Driver\Exception\InvalidArgumentException' with message 'invalid document for insert: keys cannot begin with "$": "$value"'
            if (is_array($this->systemLogInfo)) {
                $arrayKeys = array_keys($this->systemLogInfo);
                foreach ($arrayKeys as $arrayKey) {
                    $arrayKeyNew = $arrayKey;
                    if (substr($arrayKey, 0, 1) === '$') {
                        // -- If the first character of the string starts with $ then we can't save it to MongoDB, so we move this to a new entry
                        $arrayKeyNew = ltrim($arrayKey, '$');

                        // Looks like the array key was simply '$' and now we've removed that it's empty, so we'll name it dollarSign-1 or how many characters long the array key is (e.g $$$ = 'dollarSign-3' )
                        if (empty($arrayKeyNew)) {
                            $arrayKeyNew = 'dollarSign-' . strlen($arrayKey); // Add the number of dollar signs
                        }
                    }

                    // Remove '.' dots from the key, MongoDB barfs at them too
                    $arrayKeyNew = str_replace('.', '-', $arrayKeyNew);
                    if ($arrayKeyNew !== $arrayKey) {
                        $this->systemLogInfo[$arrayKeyNew] = $this->systemLogInfo[$arrayKey]; // Migrate data
                        unset($this->systemLogInfo[$arrayKey]); // Remove invalid entry
//                        \Yii::debug("Renamed the invalid systemLog entry array key '{$arrayKey}' to '{$arrayKeyNew}'"); // This doesn't get output, because this method is used to output a different line
                    }

                }
            }
            return $this->systemLogInfo;
        }
        return null;
    }


    public function getName()
    {
        return $this->name;
    }


}