<?php

class FreshdeskAgents extends DataObject
{
    private static $db = [
        'AgentId' => 'Varchar(32)',
        'Name' => 'Varchar(255)',
    ];
}
