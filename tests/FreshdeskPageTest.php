<?php

class FreshdeskPageTest extends SapphireTest
{
    protected $usesDatabase = true;

    private $page;

    private $mockService;

    public function setUp()
    {
        parent::setUp();

        Injector::nest();

        $this->mockService = $this->getMock('FreshdeskService', [
            'getUserTickets',
            'getStatuses',
        ]);

        Injector::inst()->registerService($this->mockService, 'FreshdeskService');
        $this->page = FreshdeskPage_Controller::create();
    }

    public function tearDown()
    {
        Injector::unnest();

        parent::tearDown();
    }

    public function testFiltering()
    {
        $this->logInWithPermission('ADMIN');

        $this->mockService->expects($this->any())
            ->method('getUserTickets')
            ->will($this->returnValue([
                ['status' => 'Pending', 'priority' => 'urgent'],
                ['status' => 'Open', 'priority' => 'urgent'],
                ['status' => 'Pending', 'priority' => 'urgent'],
                ['status' => 'Resolved', 'priority' => 'urgent'],
                ['status' => 'Pending', 'priority' => 'medium'],
                ['status' => 'Open', 'priority' => 'urgent'],
                ['status' => 'Pending', 'priority' => 'urgent'],
                ['status' => 'Resolved', 'priority' => 'urgent'],
                ['status' => 'Open', 'priority' => 'medium'],
                ['status' => 'Open', 'priority' => 'medium'],
                ['status' => 'Open', 'priority' => 'medium'],
                ['status' => 'Open', 'priority' => 'medium'],
                ['status' => 'Open', 'priority' => 'medium'],
                ['status' => 'Open', 'priority' => 'medium'],
            ]));

        $this->mockService->expects($this->once())
            ->method('getStatuses')
            ->will($this->returnValue([
                'Open',
                'Resolved',
                'Pending',
            ]));

        $tickets1 = $this->page->getTickets(['priority' => 'medium', 'status' => 'Open']);
        $this->assertEquals(6, count($tickets1));

        $tickets2 = $this->page->getTickets(['priority' => 'medium']);
        $this->assertEquals(7, count($tickets2));

        $tickets3 = $this->page->getTickets(['status' => 'Pending']);
        $this->assertEquals(4, count($tickets3));

        $tickets4 = $this->page->getTickets(['status' => 'banana']);
        $this->assertEquals(14, count($tickets4));

        $tickets5 = $this->page->getTickets(['priority' => 'medium', 'status' => 'any']);
        $this->assertEquals(7, count($tickets5));
    }
}
