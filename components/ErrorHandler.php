<?php

namespace mozzler\base\components;

use yii\base\Exception;
use yii\base\ErrorException;
use yii\base\UserException;
use yii\web\HttpException;
use mozzler\base\error\PassthroughApiException;

/**
 * A custom error handler that supports providing detailed error messages
 * for APIs
 */
class ErrorHandler extends \yii\web\ErrorHandler
{

    public $detailedApiMessages = true;

    /**
     * Override the default conversion of exceptions to arrays
     * to include detailed information if an API, but exclude sensitive
     * information (file, line, stack-trace) if not debug mode
     *
     * If it's a PassthroughApiException then the message is JSON encoded and should be returned decoded
     */
    protected function convertExceptionToArray($exception)
    {
        if (\Yii::$app->t::isApi() && $this->detailedApiMessages) {
            $array = [
                'name' => ($exception instanceof Exception || $exception instanceof ErrorException) ? $exception->getName() : 'Exception',
                'message' => $exception instanceof PassthroughApiException ? json_decode($exception->getMessage()) : $exception->getMessage(),
                'code' => $exception->getCode(),
            ];
            if ($exception instanceof HttpException) {
                if (!isset($array['code'])) {
                    $array['status'] = $exception->statusCode;
                }
            }

            $array['type'] = get_class($exception);

            if (!$exception instanceof UserException && YII_DEBUG) {
                $array['file'] = $exception->getFile();
                $array['line'] = $exception->getLine();
                $array['stack-trace'] = explode("\n", $exception->getTraceAsString());
                if ($exception instanceof \yii\db\Exception) {
                    $array['error-info'] = $exception->errorInfo;
                }
            }

            return $array;
        }

        return parent::convertExceptionToArray($exception);
    }

}