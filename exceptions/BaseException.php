<?php

namespace mozzler\base\exceptions;

use Throwable;
use Yii;
use yii\web\HttpException;

/**
 * Class BaseException
 * @package mozzler\base\exceptions
 *
 * This is used by the API to generate various exceptions
 * and have the default message and code added from the params
 *
 * It's expected you'll be logging Yii:error exceptions and
 */
class BaseException extends HttpException
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
    public function __construct($message = "", $code = 0, \Throwable $previous = null, $systemLogInfo = null)
    {
        $this->setVars($message, $code, $systemLogInfo);
        parent::__construct($this->code, $this->message, $this->code, $previous); // When based off the HttpException we are using $code as the HTTP Status
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
            return $this->systemLogInfo;
        }
        return null;
    }


    public function getName()
    {
        return $this->name;
    }

}