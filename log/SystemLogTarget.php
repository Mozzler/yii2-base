<?php


namespace mozzler\base\log;

use mozzler\base\components\Tools;
use mozzler\base\models\SystemLog;
use Yii;
use yii\helpers\VarDumper;
use yii\log\Logger;
use yii\log\LogRuntimeException;
use yii\log\Target;

/**
 * Class SystemLogTarget
 * @package mozzler\base\log
 */
class SystemLogTarget extends Target
{

    /**
     * Initializes the route.
     * This method is invoked after the route is created by the route manager.
     */
    public function init()
    {
        parent::init();
    }

    public function export()
    {
        foreach ($this->messages as $messageIndex => $message) {


//            $text = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n";
//            $summary = $this->collectRequest();
            /** @var SystemLog $systemLog */
            $systemLog = Tools::createModel(SystemLog::class, [

                'request' => $this->collectRequest(),
                'message' => $this->formatMessage($message)
                'data' =>

            ]);
            $save = $systemLog->save(true, null, false);
            if (!$save) {
                throw new LogRuntimeException('Unable to export SystemLog - ' . json_encode($systemLog->getErrors()));
            }
        }
    }

//    /**
//     * Writes log messages to syslog.
//     * Starting from version 2.0.14, this method throws LogRuntimeException in case the log can not be exported.
//     * @throws LogRuntimeException
//     */
//    public function export()
//    {
//        openlog($this->identity, $this->options, $this->facility);
//        foreach ($this->messages as $message) {
//            if (syslog($this->_syslogLevels[$message[1]], $this->formatMessage($message)) === false) {
//                throw new LogRuntimeException('Unable to export log through system log!');
//            }
//        }
//        closelog();
//    }


    /**
     * Collects summary data of current request.
     * @return array
     */
    protected function collectRequest()
    {
        if (Yii::$app === null) {
            return [];
        }

        $request = Yii::$app->getRequest();
        $response = Yii::$app->getResponse();

        $senderIp = $request->getUserIP(); // Most likely a proxy server
        $userIP = self::getRealIpAddr(); // Most likely the actual user's IP
        $summary = [
            'tag' => $this->tag,
            'url' => $request->getUrl(),
            'ajax' => (int)$request->getIsAjax(),
            'method' => $request->getMethod(),
            'userAgent' => $request->getUserAgent(),
            'absoluteUrl' => $request->getAbsoluteUrl(),
            'userIP' => ($senderIp === $userIP) ? $userIP : "{$userIP}, {$senderIp}", // More likely to show the actual users IP address first, then the proxy server,
            'time' => $_SERVER['REQUEST_TIME_FLOAT'],
            'statusCode' => $response->statusCode,
        ];

        return $summary;
    }


//
//    /**
//     * {@inheritdoc}
//     */
//    public function formatMessage($message)
//    {
//        list($text, $level, $category, $timestamp) = $message;
//        $level = Logger::getLevelName($level);
//        if (!is_string($text)) {
//            // exceptions may not be serializable if in the call stack somewhere is a Closure
//            if ($text instanceof \Throwable || $text instanceof \Exception) {
//                $text = (string)$text;
//            } else {
//                $text = VarDumper::export($text);
//            }
//        }
//
//        $prefix = $this->getMessagePrefix($message);
//        return "{$prefix}[$level][$category] $text";
//    }

    /**
     * @return string
     * Based off https://gist.github.com/stavrossk/6233630
     *
     * @see returnYiiRequestAsHumanReadable
     */
    public static function getRealIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) //to check ip passed from proxy
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}
