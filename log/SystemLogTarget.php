<?php


namespace mozzler\base\log;
use mozzler\base\components\Tools;
use mozzler\base\models\SystemLog;
use Yii;
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
        $text = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n";

        $summary = $this->collectRequest();
        $systemLog = Tools::createModel(SystemLog::class, [

            'request' => $this->collectRequest()

        ]);
    }


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
            'url' => $request->getAbsoluteUrl(),
            'ajax' => (int) $request->getIsAjax(),
            'method' => $request->getMethod(),
            'userAgent' => $request->getUserAgent(),
            'absoluteUrl' => $request->getAbsoluteUrl(),
            'bodyParams' => self::sanitiseParamsPassword($request->getBodyParams()),
            'userIP' => ($senderIp === $userIP) ? $userIP : "{$userIP}, {$senderIp}", // More likely to show the actual users IP address first, then the proxy server,
            'time' => $_SERVER['REQUEST_TIME_FLOAT'],
            'headers' => json_encode($request->getHeaders()),
            'statusCode' => $response->statusCode,
        ];

        return $summary;
    }


    protected function getTrace() {

//        $this->getTraceAsString()
    }
}
