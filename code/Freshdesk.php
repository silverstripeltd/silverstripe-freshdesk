<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\str;
use GuzzleHttp\Exception\RequestException;

class Freshdesk extends \Object
{
    /**
     * This is used by the SilverStripe Injector to instantiate an object as $this->client. See _config/config.yml.
     *
     * @var array
     */
    private static $dependencies = [
        'client' => '%$FreshdeskClient',
    ];

    /**
    * Generic method to handle requests to the Freshdesk API
    *
    * @param String $method, String $url, String $action, Array $headers, Array $data
    * @return $response || false
    */
    public function APICall($method, $url, $action, $headers, $data = [])
    {
        $request = new Request($method, 'https://'.$url.$action, $headers);

        try {
            $response = $this->client->send($request, ['json' => $data]);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                SS_Log::log(str($e->getResponse()), SS_Log::ERR);
            }
            $response = false;
        }
        return $response;
    }
}
