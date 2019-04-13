<?php

namespace mozzler\base\events;

use Yii;
use yii\base\Event;

class RequestEvent
{

    /**
     * @param $event Event
     * @throws \yii\web\NotFoundHttpException
     *
     * Expected to be called in the web.php $config = [
     * 'on beforeRequest' => ['mozzler\base\events\RequestEvent', 'verifyRequestModes'],
     * ...
     * ]
     */
    public function verifyRequestModes($event)
    {
        if (!isset(\Yii::$app->params['requestModes'])) {
            Yii::error("No Yii params['requestModes'] set, try adding ['web', 'api']");
            throw new \yii\web\NotFoundHttpException("You are not able to access this site");
        }
        $requestModes = \Yii::$app->params['requestModes'];
        $isApi = \mozzler\base\components\Tools::isApi();
        if ($isApi && !in_array('api', $requestModes)) {
            Yii::error("No api in the Yii params['requestModes'], rejecting the request");
            // @todo: Get this to return a JSON error response
            throw new \yii\web\NotFoundHttpException("You are not able to access the API");
        }
        if (!$isApi && !in_array('web', $requestModes)) {
            Yii::error("No web in the Yii params['requestModes'], rejecting the request");
            throw new \yii\web\NotFoundHttpException("You are not able to access this website");
        }
        Yii::info("Request modes check - isApi: " . json_encode($isApi) . " and the requestModes is " . json_encode($requestModes) . " so allowing the request");
    }

}