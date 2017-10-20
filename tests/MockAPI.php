<?php

class MockAPI extends Object
{
    public function getUserTickets()
    {
        $tickets = [
            ['status' =>  'Pending', 'priority' => 'urgent'],
            ['status' =>  'Open', 'priority' => 'urgent'],
            ['status' =>  'Pending', 'priority' => 'urgent'],
            ['status' =>  'Resolved', 'priority' => 'urgent'],
            ['status' =>  'Pending', 'priority' => 'medium'],
            ['status' =>  'Open', 'priority' => 'urgent'],
            ['status' =>  'Pending', 'priority' => 'urgent'],
            ['status' =>  'Resolved', 'priority' => 'urgent'],
            ['status' =>  'Open', 'priority' => 'medium'],
            ['status' =>  'Open', 'priority' => 'medium'],
            ['status' =>  'Open', 'priority' => 'medium'],
            ['status' =>  'Open', 'priority' => 'medium'],
            ['status' =>  'Open', 'priority' => 'medium'],
            ['status' =>  'Open', 'priority' => 'medium'],
        ];
        return $tickets;
    }

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

    public function getStatuses()
    {
        $statuses = [
            0 => 'Open',
            1 => 'Resolved',
            2 => 'Pending',
        ];

        return $statuses;
    }
}
