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
    * Returns Freshdesk tickets for a user
    * Valid filters: cc_emails, fwd_emails, reply_cc_emails, fr_escalated, spam, email_config_id, group_id, priority, source, company_id, status, subject, to_emails, product_id, id type, due_by, fr_due_by, is_escalated, description
    *
    * @param Array $filter
    * @return PaginatedList
    *
    */
    public function getFreshdeskTickets($filter = [])
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
    private function freshdeskTicketFilter($tickets, $productID = false, $filter = [])
    {
        $tickets = $this->humanReadable($tickets);
        // fast return if nothing to filter on
        if (!$productID && !$filter) {
            return $tickets;
        }

        foreach ($tickets as $key => $ticket) {
            if ($productID && $ticket['product_id'] != $productID) {
                unset($tickets[$key]);
                continue;
            }

            if (empty($filter) || count($filter) == 0) {
                continue;
            }

            if (!array_map('doFilter', $ticket, $filters)) {
                unset($tickets[$key]);
            }
        }
        return $tickets;
    }

    private function doFilter($ticket, $filters)
    {
        foreach ($filters as $filter => $value) {
            if ($ticket[$filter] == $value) {
                return true;
            }
        }
        return false;
    }

    /**
    * Converts the numeric values in a ticket to something that makes sense to a human. Mappings defined as statics on this class
    *
    * @param Array $tickets
    * @return Array $formattedTickets
    */
    private function humanReadable($tickets)
    {
        $priorities = self::$freshdeskPriority;
        $statuses = self::$freshdeskStatus;
        $formattedTickets = [];
        foreach ($tickets as $ticket) {
            $ticket['priority'] = $priorities[$ticket['priority']];
            $ticket['status'] = $statuses[$ticket['status']];
            $formattedTickets[] = $ticket;
        }
        return $formattedTickets;
    }
}
