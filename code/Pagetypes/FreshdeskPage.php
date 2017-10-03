<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

class FreshdeskPage extends Page
{

}

class FreshdeskPage_Controller extends Page_Controller
{
    /**
    * Status mapping for Freshdesk tickets
    * @var Array
    * @config
    */
    private static $freshdeskStatus = [
        2 => 'open',
        3 => 'pending',
        4 => 'resolved',
        5 => 'closed',
    ];

    /**
    * Priority mapping for Freshdesk tickets
    * @var Array
    * @config
    */
    private static $freshdeskPriority = [
        1 => 'low',
        2 => 'medium',
        3 => 'high',
        4 => 'urgent',
    ];

    /**
    * @var Array
    */
    private static $_tickets = [];

    /**
    * Get tickets from in memory cache, otherwise put them there
    *
    * @return Array $_tickets
    */
    private function getTickets($currentMember)
    {
        if (!self::$_tickets) {
            $headers = ["Content-type" => "application/json"];
            $freshdesk = \FreshdeskAPI::create();
            $result = $freshdesk->APICall('GET', FRESHDESK_API_BASEURL, '/api/v2/tickets?email='.urlencode($currentMember->Email), $headers);

            $tickets = [];
            if ($result && $result->getStatusCode() == '200') {
                $tickets = json_decode($result->getBody()->getContents(), true);
            }
            self::$_tickets = $tickets;
        }
        return self::$_tickets;
    }

    /*
    * Returns Freshdesk tickets for a user can be filtered by priority or status
    *
    * @param Array $filter
    * @return PaginatedList
    *
    */
    public function getFreshdeskTickets()
    {
        $currentMember = \Member::currentUser();
        if (!$currentMember || !$currentMember->exists()) {
            return \Security::permissionFailure();
        }

        $tickets = $this->getTickets($currentMember);

        if (empty($tickets) || count($tickets) == 0) {
            return false;
        }

        $productID = false;
        if (defined("FRESHDESK_PRODUCT_ID")) {
            $productID = FRESHDESK_PRODUCT_ID;
        }

        // Get all open by default
        $filter = ['status' => 2];
        if ($this->request->getVar('status')) {
            $filter['status'] = $this->request->getVar('status');
        }
        if ($this->request->getVar('priority')) {
            $filter['priority'] = $this->request->getVar('priority');
        }
        $filter = $this->validateFilter($filter);

        $tickets = $this->freshdeskTicketFilter($tickets, $productID, $filter);
        $tickets = $this->humanReadable($tickets);
        $tickets = new ArrayList($tickets);
        return new PaginatedList($tickets, $this->request);
    }

    /**
    * Filters tickets for a user based on product ID or other filter
    *
    * @param Array $tickets, String $productID, Array $filter
    * @return Array $tickets
    */
    private function freshdeskTicketFilter($tickets, $productID = false, $filter)
    {
        // fast return if nothing to filter on
        if (!$productID && !$filter) {
            return $tickets;
        }

        foreach ($tickets as $key => $val) {
            if ($productID && $val['product_id'] != $productID) {
                unset($tickets[$key]);
                continue;
            }

            if (empty($filter) || count($filter) == 0) {
                continue;
            }

            foreach ($filter as $filterKey => $filterVal) {
                $doFilter = $this->doFilter($val, $filterKey, $filterVal);
                if ($doFilter) {
                    unset($tickets[$key]);
                }
            }
        }
        return $tickets;
    }

    /**
    * Return true or false, depending on if a ticket should be removed based on a match to a filter
    *
    * @param Array $ticket, String $filterKey, String $filterVal
    * @return Boolean
    */
    private function doFilter($ticket, $filterKey, $filterVal)
    {
        if ($ticket[$filterKey] == (int) $filterVal) {
            return false;
        }
        return true;
    }

    /**
    * Converts the numeric values in a ticket to something that makes sense to a human. Mappings defined as statics on this class
    *
    * @param Array $tickets
    * @return Array $formattedTickets
    */
    private function humanReadable($tickets)
    {
        $priorities = Config::inst()->get('FreshdeskPage_Controller', 'freshdeskPriority');
        $statuses = Config::inst()->get('FreshdeskPage_Controller', 'freshdeskStatus');
        $formattedTickets = [];
        foreach ($tickets as $ticket) {
            $ticket['priority'] = $priorities[$ticket['priority']];
            $ticket['status'] = $statuses[$ticket['status']];
            $formattedTickets[] = $ticket;
        }
        return $formattedTickets;
    }

    /**
    * Ensures filter only contains allowed statuses and priorities. If unallowed it is unset
    *
    * @param Array $filter
    * @return Array $filter
    */
    private function validateFilter($filter)
    {
        $priorities = Config::inst()->get('FreshdeskPage_Controller', 'freshdeskPriority');
        if (isset($filter['priority'])) {
            if (!in_array($filter['priority'], array_keys($priorities))) {
                unset($filter['priority']);
            }
        }

        $statuses = Config::inst()->get('FreshdeskPage_Controller', 'freshdeskStatus');
        if (!in_array($filter['status'], array_keys($statuses))) {
            unset($filter['status']);
        }

        return $filter;
    }

    /**
    * Renders a form which can be used for filtering the tickets via template
    *
    * @return Form $form
    */
    public function filterForm()
    {
        $statuses = Config::inst()->get('FreshdeskPage_Controller', 'freshdeskStatus');
        $currentStatus = 2;
        if ($this->request->getVar('status') && $this->request->getVar('status') != null) {
            $currentStatus = $this->request->getVar('status');
        }

        $priorities = Config::inst()->get('FreshdeskPage_Controller', 'freshdeskPriority');
        $priorities[0] = "any";
        $currentPriority = 0;
        if ($this->request->getVar('priority') && $this->request->getVar('priority') != null) {
            $currentPriority = $this->request->getVar('priority');
        }

        $fields = new FieldList(
            DropdownField::create('status', 'status', $statuses, $currentStatus),
            DropdownField::create('priority', 'priority', $priorities, $currentPriority)
        );

        $actions = new FieldList(
            FormAction::create('Filter', 'Filter')
        );

        $form = new Form($this, '', $fields, $actions);
        $form->setTemplate('FilterForm');
        $form->setFormMethod('get');

        return $form;
    }
}
