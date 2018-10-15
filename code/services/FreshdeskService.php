<?php

use Freshdesk\Api;

class FreshdeskService extends \Object
{
    /**
     * @var null|Api
     */
    private $client;

    /**
     * @var array
     * @config
     */
    private static $priorities = [
        'any',
        'low',
        'medium',
        'high',
        'urgent',
    ];

    public function __construct()
    {
        $this->client = $this->createApi();
    }

    public function enabled()
    {
        return defined('FRESHDESK_DOMAIN') && defined('FRESHDESK_TOKEN');
    }

    /**
     * @param array $filter
     *
     * @return array
     */
    public function getContacts(array $filter = [])
    {
        return $this->getAllPagedResults(function ($page) use ($filter) {
            return $this->client->contacts->all(array_merge($filter, [
                'per_page' => 100,
                'page' => $page,
            ]));
        });
    }

    /**
     * @param array $filter
     *
     * @return array
     */
    public function getAgents(array $filter = [])
    {
        return $this->getAllPagedResults(function ($page) use ($filter) {
            return $this->client->agents->all(array_merge($filter, [
                'per_page' => 100,
                'page' => $page,
            ]));
        });
    }

    /**
     * @param Member $member
     * @param array  $filter
     *
     * @return null|array
     */
    public function getUserTickets(Member $member, array $filter = [])
    {
        if (!$this->checkValidUser($member)) {
            return null;
        }

        $params = array_merge($filter, ['email' => urlencode($member->Email)]);
        $result = $this->client->tickets->all($params);
        $tickets = $this->humanReadable($result);

        return $tickets;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function makeTicket($data)
    {
        return $this->client->tickets->create($data);
    }

    /**
     * @return array
     */
    public function getStatuses()
    {
        $fields = $this->client->tickets->fields(['type' => 'default_status']);
        $choices = [];
        foreach ($fields[0]['choices'] as $choice) {
            $choices[] = $choice[0];
        }

        return $choices;
    }

    /**
     * @return null|Api
     */
    private function createApi()
    {
        if (!$this->enabled()) {
            return null;
        }

        return new Api(FRESHDESK_TOKEN, FRESHDESK_DOMAIN);
    }

    /**
     * @param callable $func
     *
     * @return array
     */
    private function getAllPagedResults(callable $func)
    {
        $results = [];
        $page = 1;
        while (true) {
            $resp = $func($page);
            if (empty($resp)) {
                break;
            }
            $results = array_merge($results, $resp);
            ++$page;
        }

        return $results;
    }

    /**
     * @return array
     */
    private function getFieldMappings()
    {
        return $this->client->tickets->fields();
    }

    /**
     * Converts the numeric values in a ticket to something that makes sense to a human.
     *
     * @param array $tickets
     *
     * @return array
     */
    private function humanReadable($tickets)
    {
        $priorities = $this->config()->priorities;
        $statuses = $this->getStatuses();
        $agents = $this->getAgents();

        $formattedAgents = [];
        foreach ($agents as $agent) {
            $formattedAgents[] = ['id' => $agent['id'], 'name' => $agent['contact']['name']];
        }
        $agents = new ArrayList($formattedAgents);

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
     * @param Member $member
     *
     * @return bool
     */
    private function checkValidUser(Member $member)
    {
        $contact = $this->client->contacts->all(['email' => urlencode($member->Email)]);
        if (1 === count($contact)) {
            return true;
        }

        $agent = $this->client->agents->all(['email' => urlencode($member->Email)]);
        if (1 === count($agent)) {
            return true;
        }

        return false;
    }
}
