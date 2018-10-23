<?php

class FreshdeskPage extends Page
{
}

class FreshdeskPage_Controller extends Page_Controller
{
    /**
     * @var FreshdeskService
     */
    public $freshdeskService;

    private static $dependencies = [
        'freshdeskService' => '%$FreshdeskService',
    ];

    private static $_cache_states;

    /**
     * Returns Freshdesk tickets for a user.
     *
     * @param array $filter
     *
     * @return mixed
     */
    public function getTickets(array $filter = ['status' => '', 'priority' => ''])
    {
        $member = Member::currentUser();
        if (!$member || !$member->exists()) {
            return Security::permissionFailure();
        }

        $tickets = $this->freshdeskService->getUserTickets($member);
        if (empty($tickets)) {
            return null;
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
     * @param array $filter
     *
     * @return ArrayList
     */
    private function filterTickets(array $tickets, array $filter)
    {
        $tickets = new ArrayList($tickets);

        if (defined('FRESHDESK_PRODUCT_ID') && FRESHDESK_PRODUCT_ID) {
            $tickets = $tickets->filter('product_id', FRESHDESK_PRODUCT_ID);
        }

        $filter = $this->validateFilter($filter);

        if ($filter) {
            foreach ($filter as $key => $value) {
                $tickets = $tickets->filter($key, $value);
            }
        }

        return $tickets;
    }

    /**
     * Ensures filter only contains allowed statuses and priorities. If unallowed it is unset.
     *
     * @param array $filter
     *
     * @return array
     */
    private function validateFilter(array $filter)
    {
        $states = self::$_cache_states;
        if (!isset($states)) {
            $states = $this->freshdeskService->getStatuses();
            self::$_cache_states = $states;
        }

        $priorities = Config::inst()->get('FreshdeskService', 'priorities');

        if (isset($filter['priority'])) {
            if (!in_array($filter['priority'], array_values($priorities)) || ('any' === $filter['priority'])) {
                unset($filter['priority']);
            }
        }

        if (isset($filter['status'])) {
            if (!in_array($filter['status'], array_values($states)) || ('any' === $filter['status'])) {
                unset($filter['status']);
            }
        }

        return $filter;
    }
}
