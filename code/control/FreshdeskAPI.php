<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;

class FreshdeskAPI extends \Object
{
    /**
     * @var int
     */
    const maxRetries = 5;

    /**
     * @var array
     */
    private $client;

    /**
     * @var array
     */
    private static $_tickets = [];

    /**
     * @var array
     */
    private static $_field_mappings = [];

    /**
     * @var array
     */
    private static $_agents = [];

    /**
     * @var array
     */
    private static $_statuses = [];

    public function __construct()
    {
        $this->client = $this->createClient();
    }

    public function enabled()
    {
        return defined('FRESHDESK_API_BASEURL') && defined('FRESHDESK_API_TOKEN');
    }

    /**
     * Get tickets for a specific user.
     *
     * @param mixed $currentMember
     *
     * @return array $_tickets
     */
    public function getUserTickets($currentMember)
    {
        if (self::$_tickets) {
            return self::$_tickets;
        }

        if (!$this->enabled()) {
            return null;
        }

        if (!$this->checkValidUser($currentMember)) {
            return false;
        }

        $result = $this->call('GET', FRESHDESK_API_BASEURL, '/api/v2/tickets?email='.urlencode($currentMember->Email));

        $tickets = [];
        if ($result && '200' == $result->getStatusCode()) {
            $tickets = json_decode($result->getBody()->getContents(), true);
        }

        $tickets = $this->humanReadable($tickets);

        self::$_tickets = $tickets;

        return self::$_tickets;
    }

    /**
     * Creates a Freshdesk ticket.
     *
     * @param mixed $data
     *
     * @throws Exception
     */
    public function makeTicket($data)
    {
        if (!$data) {
            throw new Exception('No data provided to create ticket', 1);
        }

        return $this->call('POST', FRESHDESK_API_BASEURL, '/api/v2/tickets', $data);
    }

    /**
     * Get all agents on the FD.
     *
     * @return array $agents
     */
    public function getAgents()
    {
        if (self::$_agents) {
            return self::$_agents;
        }

        if (!$this->enabled()) {
            return null;
        }

        $agentResult = $this->call('GET', FRESHDESK_API_BASEURL, '/api/v2/agents');

        $agents = [];
        if ($agentResult && $agentResult->getStatusCode() == '200') {
            $agents = json_decode($agentResult->getBody()->getContents(), true);
        }

        $formattedAgents = [];
        foreach ($agents as $agent) {
            $formattedAgents[] = ['id' => $agent['id'], 'name' => $agent['contact']['name']];
        }

        self::$_agents = $formattedAgents;

        return self::$_agents;
    }

    /**
     * Get all statuses on the FD.
     *
     * @return array $choices
     */
    public function getStatuses()
    {
        if (self::$_statuses) {
            return self::$_statuses;
        }

        if (!$this->enabled()) {
            return null;
        }

        $statusResult = $this->call('GET', FRESHDESK_API_BASEURL, '/api/v2/ticket_fields?type=default_status');

        $statuses = [];
        if ($statusResult && $statusResult->getStatusCode() == '200') {
            $statuses = json_decode($statusResult->getBody()->getContents(), true);
        }
        $choices = [];
        foreach ($statuses[0]['choices'] as $choice) {
            $choices[] = $choice[0];
        }

        self::$_statuses = $choices;

        return self::$_statuses;
    }

    /**
     * Sets up the Guzzle client for this class.
     *
     * @return Client $client
     */
    public function createClient()
    {
        if (!$this->enabled()) {
            return null;
        }

        $handlerStack = HandlerStack::create(new CurlHandler());
        // setup a retry functionality that will retry the request self::maxRetries for connection errors and 500 status code
        // errors. This will by default have an exponential back-off (sleep) between retries calculated as pow(2, $retries - 1);
        $handlerStack->push(Middleware::retry(
            function ($retries, $request, $response, $exception) {
                if ($retries > self::maxRetries) {
                    return false;
                }
                if ($exception instanceof ConnectException) {
                    return true;
                }
                if ($response && $response->getStatusCode() >= 500) {
                    return true;
                }

                return false;
            },
            function ($retries) {
                // back-off, aka sleep between retries in milliseconds
                return (int) 2 ** ($retries - 1) * 10;
            }
        ));
        $client = new Client([
            'handler' => $handlerStack,
            'timeout' => 10.0,
            'connect_timeout' => 5.0,
            'auth' => [FRESHDESK_API_TOKEN, 'X'],
        ]);

        return $client;
    }

    /**
     * Returns priorities mapped to human readable.
     *
     * @return array $priorities
     */
    public function getPriorities()
    {
        $priorities = [
            0 => 'any',
            1 => 'low',
            2 => 'medium',
            3 => 'high',
            4 => 'urgent',
        ];

        return $priorities;
    }

    /**
     * Generic method to handle requests to the Freshdesk API.
     *
     * @param string $method, String $url, String $action, Array $headers, Array $data
     * @param mixed  $url
     * @param mixed  $action
     * @param mixed  $data
     * @param mixed  $headers
     *
     * @return $response || false
     */
    private function call($method, $url, $action, $data = [], $headers = ['Content-type' => 'application/json'])
    {
        $request = new Request($method, 'https://'.$url.$action, $headers);

        try {
            $response = $this->client->send($request, ['json' => $data]);
        } catch (RequestException $e) {
            SS_Log::log($e->getMessage(), SS_Log::ERR);
            throw new Exception($e->getMessage(), 1);
        }

        return $response;
    }

    /**
     * get all fields in FD tickets.
     *
     * @return array $fields
     */
    private function getFieldMappings()
    {
        if (self::$_field_mappings) {
            return self::$_field_mappings;
        }

        $result = $this->call('GET', FRESHDESK_API_BASEURL, '/api/v2/ticket_fields');

        $fields = [];
        if ($result && $result->getStatusCode() == '200') {
            $fields = json_decode($result->getBody()->getContents(), true);
        }

        self::$_field_mappings = $fields;

        return self::$_field_mappings;
    }

    /**
     * Converts the numeric values in a ticket to something that makes sense to a human.
     *
     * @param array $tickets
     *
     * @return ArrayList $formattedTickets
     */
    private function humanReadable($tickets)
    {
        $priorities = $this->getPriorities();
        $statuses = $this->getStatuses();
        $agents = new ArrayList($this->getAgents());

        $formattedTickets = [];
        foreach ($tickets as $ticket) {
            if (isset($ticket['responder_id'])) {
                $responder = $agents->find('id', $ticket['responder_id']);
                $ticket['responder'] = $responder['name'];
            }

            if (isset($statuses[$ticket['status']])) {
                $ticket['status'] = $statuses[$ticket['status']];
            }

            if (isset($priorities[$ticket['priority']])) {
                $ticket['priority'] = $priorities[$ticket['priority']];
            }

            if (isset($ticket['custom_fields'])) {
                foreach ($ticket['custom_fields'] as $key => $value) {
                    $ticket[$key] = $value;
                }
                unset($ticket['custom_fields']);
            }

            $formattedTickets[] = $ticket;
        }

        return $formattedTickets;
    }

    /**
     * Returns true if contact exists.
     *
     * @param mixed $currentMember
     *
     * @return bool
     */
    private function checkValidUser($currentMember)
    {
        $result = $this->call('GET', FRESHDESK_API_BASEURL, '/api/v2/contacts?email='.urlencode($currentMember->Email));

        if ($result && $result->getStatusCode() == '200') {
            $contact = json_decode($result->getBody()->getContents(), true);
        }

        if (count($contact) == 1) {
            return true;
        }

        $agentResult = $this->call('GET', FRESHDESK_API_BASEURL, '/api/v2/agents?email='.urlencode($currentMember->Email));

        if ($agentResult && $agentResult->getStatusCode() == '200') {
            $agent = json_decode($agentResult->getBody()->getContents(), true);
        }

        if (count($agent) == 1) {
            return true;
        }

        return true;
    }
}
