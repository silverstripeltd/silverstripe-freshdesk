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
    * @param Array $data, Array $headers, String $method, String $url, String $action
    * @return Boolean
    */
    public function APICall($data, $headers, $method, $url, $action)
    {
        $request = new Request($method, 'https://'.$url.$action, $headers);

        try {
            $response = $this->client->send($request, ['json' => $data]);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                SS_Log::log(str($e->getResponse()), SS_Log::ERR);
            }
            return false;
        }
        return true;
    }
}
