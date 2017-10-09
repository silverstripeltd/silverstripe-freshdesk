<?php

class FreshdeskTicketStatuses extends DataObject
{
    private static $db = [
        'StatusId' => 'Varchar(32)',
        'Name' => 'Varchar(255)',
    ];
}
