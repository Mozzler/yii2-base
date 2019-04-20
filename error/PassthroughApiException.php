<?php

namespace mozzler\base\error;

use Throwable;
use yii\web\HttpException;

/**
 * Class PassthroughApiException
 *
 * This is used by the API to return JSON encoded messages
 * which are shown json_decoded by the mozzler\base\components\ErrorHandler
 */
class PassthroughApiException extends HttpException
{

    public function __construct($apiResponse, $code = 400, Throwable $previous = null)
    {
        $response = ''; // Init
        // -- Check if it's a valid response object
        if (empty($apiResponse)) {
            $response = json_encode(["Message" => "Unknown Error"]);
        } else if (method_exists($apiResponse, 'getBody') && !empty((string)$apiResponse->getBody())) {
            // -- If provided with a Guzzle object or anything with a getBody function
            /** @var $apiResponse \GuzzleHttp\Psr7\Response */
            $response = (string)$apiResponse->getBody();

            // -- Set the same status code as the Viterra response
            if (method_exists($apiResponse, 'getStatusCode') && !empty($apiResponse->getStatusCode())) {
                $code = $apiResponse->getStatusCode();
            }
        } else {
            $response = json_encode($apiResponse);
        }

        $this->message = $response;
        $this->code = $code;
        $this->statusCode = $code;

    }

}