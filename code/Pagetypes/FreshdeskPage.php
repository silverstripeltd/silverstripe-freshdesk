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
    private static $freshdeskStatus = [
        2 => 'Open',
        3 => 'Pending',
        4 => 'Resolved',
        5 => 'Closed',
    ];

    private static $freshdeskPriority = [
        1 => 'Low',
        2 => 'Medium',
        3 => 'High',
        4 => 'Urgent',
    ];

    /*
     *
     * Valid filters: cc_emails, fwd_emails, reply_cc_emails, fr_escalated, spam, email_config_id
     *     group_id, priority, source, company_id, status, subject, to_emails, product_id, id
     *     type, due_by, fr_due_by, is_escalated, description
     *
     */

    public function getFreshdeskTickets($filter = [])
    {
        $currentMember = \Member::currentUser();
        if (!$currentMember || !$currentMember->exists()) {
            return \Security::permissionFailure();
        }

        $headers = ["Content-type" => "application/json"];

        $freshdesk = \Freshdesk::create();
        $res = $freshdesk->APICall('GET', FRESHDESK_API_BASEURL, '/api/v2/tickets?email='.urlencode($currentMember->Email), $headers);

        if ($res->getStatusCode() == '200') {
            $tickets = json_decode($res->getBody()->getContents(), true);
        }

        if (empty($tickets) || count($tickets) == 0) {
            return false;
        }

        if (defined("FRESHDESK_PRODUCT_ID")) {
            $productID = FRESHDESK_PRODUCT_ID;
        } else {
            $productID = false;
        }

        $tickets = $this->freshdeskFilter($tickets, $productID, $filter);

        return new ArrayList($tickets);
    }

    private function freshdeskFilter($tickets, $productID = false, $filter = [])
    {
        foreach ($tickets as $key=>$ticket) {

            if ($productID && $ticket['product_id'] != $productID) {
                unset($tickets[$key]);
                continue;
            }

            if (empty($filter) || count($filter) == 0) {
                continue;
            }

            if (!array_map('doFilters', $ticket, $filters)) {
                unset($tickets[$key]);
            }

        }
        return $tickets;
    }

    private function doFilters($ticket, $filters)
    {
        $hasFilter = false;

        foreach ($filters as $filter=>$value) {
            if ($ticket[$filter] == $value) {
                return true;
            }
        }
        return $hasFilter;
    }

}
