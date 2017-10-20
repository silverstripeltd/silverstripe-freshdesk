<?php


class FreshdeskPage extends Page
{
}

class FreshdeskPage_Controller extends Page_Controller
{
    /**
     * @var FreshdeskAPI
     */
    public $freshdesk;
    private static $dependencies = [
        'freshdesk' => '%$FreshdeskAPI',
    ];

    /**
     * Returns Freshdesk tickets for a user.
     *
     * @param array $filter
     *
     * @return PaginatedList
     */
    public function getTickets($filter = ['status' => '', 'priority' => ''])
    {
        $currentMember = \Member::currentUser();
        if (!$currentMember || !$currentMember->exists()) {
            return \Security::permissionFailure();
        }

        $tickets = $this->freshdesk->getUserTickets($currentMember);

        if (empty($tickets) || count($tickets) == 0) {
            return false;
        }

        if ($this->request->getVar('status')) {
            $filter['status'] = $this->request->getVar('status');
        }
        if ($this->request->getVar('priority')) {
            $filter['priority'] = $this->request->getVar('priority');
        }

        $tickets = $this->filterTickets($tickets, $filter);

        return new PaginatedList($tickets, $this->request);
    }

    /**
     * Filter tickets based on productID and status/priority filters.
     *
     * @param array $tickets
     * @param mixed $filter
     *
     * @return ArrayList $tickets
     */
    private function filterTickets($tickets, $filter)
    {
        $productID = false;
        if (defined('FRESHDESK_PRODUCT_ID')) {
            $productID = FRESHDESK_PRODUCT_ID;
        }

        $tickets = new ArrayList($tickets);

        if ($productID) {
            $tickets = $tickets->filter('product_id', $productID);
        }

        $filter = $this->validateFilter($filter);

        if ($filter) {
            foreach ($filter as $filterKey => $filterVal) {
                $tickets = $tickets->filter($filterKey, $filterVal);
            }
        }

        return $tickets;
    }

    /**
     * Ensures filter only contains allowed statuses and priorities. If unallowed it is unset.
     *
     * @param array $filter
     *
     * @return array $filter
     */
    private function validateFilter($filter)
    {
        if (isset($filter['priority'])) {
            if (!in_array($filter['priority'], array_values($this->freshdesk->getPriorities())) || ($filter['priority'] == 'any')) {
                unset($filter['priority']);
            }
        }

        if (isset($filter['status'])) {
            if (!in_array($filter['status'], array_values($this->freshdesk->getStatuses())) || ($filter['status'] == 'any')) {
                unset($filter['status']);
            }
        }

        return $filter;
    }
}
