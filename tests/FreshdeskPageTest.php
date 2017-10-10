<?php

class FreshdeskPageTest extends SapphireTest
{
    public $mockAPI;

    public $page;
    protected $usesDatabase = true;

    public function setUp()
    {
        parent::setUp();
        $this->mockAPI = new MockAPI();

        Injector::nest();
        Injector::inst()->registerService($this->mockAPI, 'FreshdeskAPI');

        $this->page = FreshdeskPage_Controller::create();
    }

    public function tearDown()
    {
        Injector::unnest();
    }

    public function testFiltering()
    {
        $this->setUp();

        $filter = [
            'priority' => 'medium',
            'status' => 'Open',
        ];
        $tickets1 = $this->page->getTickets($filter);
        $this->assertEquals(6, count($tickets1));

        $filter = [
            'priority' => 'medium',
        ];
        $tickets2 = $this->page->getTickets($filter);
        $this->assertEquals(7, count($tickets2));

        $filter = [
            'status' => 'Pending',
        ];
        $tickets3 = $this->page->getTickets($filter);
        $this->assertEquals(4, count($tickets3));

        $filter = [
            'status' => 'banana',
        ];
        $tickets4 = $this->page->getTickets($filter);
        $this->assertEquals(14, count($tickets4));

        $filter = [
            'priority' => 'medium',
            'status' => 'any',
        ];
        $tickets5 = $this->page->getTickets($filter);
        $this->assertEquals(7, count($tickets5));

        $this->tearDown();
    }
}
