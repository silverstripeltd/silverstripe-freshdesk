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
    */
    private static $freshdeskStatus = [
        2 => 'Open',
        3 => 'Pending',
        4 => 'Resolved',
        5 => 'Closed',
    ];

    /**
    * Priority mapping for Freshdesk tickets
    */
    private static $freshdeskPriority = [
        1 => 'Low',
        2 => 'Medium',
        3 => 'High',
        4 => 'Urgent',
    ];

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

        $headers = ["Content-type" => "application/json"];
        $freshdesk = \FreshdeskAPI::create();
        $result = $freshdesk->APICall('GET', FRESHDESK_API_BASEURL, '/api/v2/tickets?email='.urlencode($currentMember->Email), $headers);

        if ($result->getStatusCode() == '200') {
            $tickets = json_decode($result->getBody()->getContents(), true);
        }

        if (empty($tickets) || count($tickets) == 0) {
            return false;
        }

        $productID = false;
        if (defined("FRESHDESK_PRODUCT_ID")) {
            $productID = FRESHDESK_PRODUCT_ID;
        }

        // Get all open by default
        $filter = ['status' => 'open'];
        if ($this->request->getVar('status')) {
            $filter['status'] = $this->request->getVar('status');
        }
        if ($this->request->getVar('priority')) {
            $filter['priority'] = $this->request->getVar('priority');
        }
        $filter = $this->validateFilter($filter);

        $tickets = $this->humanReadable($tickets);
        $tickets = $this->freshdeskTicketFilter($tickets, $productID, $filter);
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
        if (strcasecmp($ticket[$filterKey], $filterVal) == 0) {
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
}
