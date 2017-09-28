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

        $client = new Client([
            'base_uri' => 'https://'.FRESHDESK_API_BASEURL,
            'auth' => [FRESHDESK_API_TOKEN, FRESHDESK_PASSWORD],
        ]);

        $res = $client->request('GET', '/api/v2/tickets?email='.urlencode($currentMember->Email), $headers);
        
        if ($res->getStatusCode() == '200') {
            $tickets = json_decode($res->getBody()->getContents(), true);
        }

        if (empty($tickets) || count($tickets) == 0) {
            return false;
        }

        $tickets = $this->applyFreshdeskFilter($tickets, FRESHDESK_PRODUCT_ID, $filter);

        return new ArrayList($tickets);
    }

    private function applyFreshdeskFilter($tickets, $productID = false, $filter = [])
    {

        foreach ($tickets as $key=>$ticket) {

            if ($ticket['product_id'] != $productID) {
                unset($tickets[$key]);
                continue;
            }

            if (empty($filter) || count($filter) == 0) {
                continue;            
            }

            foreach ($filters as $filter=>$value) {
                if (array_key_exists($filter, $ticket)) {
                    if ($ticket[$filter] == $value) {
                        continue;
                    }
                    unset($tickets[$key]);
                }
            }
        }
        
        return $tickets;
    }
}
