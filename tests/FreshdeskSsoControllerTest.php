<?php

use Monolog\Logger;

class FreshdeskSsoControllerTest extends FunctionalTest
{
    protected static $fixture_file = 'FreshdeskSsoControllerTest.yml';

    protected $autoFollowRedirection = false;

    public function setUp()
    {
        parent::setUp();

        Config::nest();

        Config::inst()->update('FreshdeskSsoController', 'hmac_secret', null);
        Config::inst()->update('FreshdeskSsoController', 'domain', null);
        Config::inst()->update('FreshdeskSsoController', 'portal_hostname', null);
        SS_Datetime::set_mock_now('2020-10-10 10:10:10');

        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        Injector::inst()->registerService($this->logger, 'FreshdeskAuditLogger');
    }

    public function tearDown()
    {
        parent::tearDown();

        SS_Datetime::clear_mock_now();
        Config::unnest();
    }

    public function testRedirectToLoginIfNotLoggedIn()
    {
        Config::inst()->update('FreshdeskSsoController', 'hmac_secret', '123');
        Config::inst()->update('FreshdeskSsoController', 'domain', 'myhelpdesk');

        $resp = $this->get('freshdesksso/simplelogin');

        $this->assertSame(302, $resp->getStatusCode());
        $this->assertContains('Security/login', $resp->getHeader('Location'));
    }

    public function testNotEnabledRedirectToLocalSiteRoot()
    {
        $m = $this->objFromFixture('Member', 'joe');
        $this->session()->inst_set('loggedInAs', $m->ID);

        $this->logger->expects($this->never())
            ->method('info');

        $resp = $this->get('freshdesksso/simplelogin');

        $this->assertSame(302, $resp->getStatusCode());
        $this->assertSame('/', $resp->getHeader('Location'));
    }

    public function testRedirectToFreshdesk()
    {
        $m = $this->objFromFixture('Member', 'joe');
        $this->session()->inst_set('loggedInAs', $m->ID);

        Config::inst()->update('FreshdeskSsoController', 'hmac_secret', '123');
        Config::inst()->update('FreshdeskSsoController', 'domain', 'myhelpdesk');

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->equalTo(
                'joe@joe.org authorised Freshdesk Simple SSO login request'
            ));

        $resp = $this->get('freshdesksso/simplelogin');

        $this->assertSame(302, $resp->getStatusCode());
        $this->assertSame(
            'https://myhelpdesk.freshdesk.com/login/sso/?name=Joe+Bloggs&email=joe%40joe.org&timestamp=1602324610&hash=3d4b54ff08b74733437e757fa6fb44fa',
            $resp->getHeader('Location')
        );
    }

    public function testRedirectToLocalSiteRootAfterLogout()
    {
        $m = $this->objFromFixture('Member', 'joe');
        $this->session()->inst_set('loggedInAs', $m->ID);

        Config::inst()->update('FreshdeskSsoController', 'hmac_secret', '123');
        Config::inst()->update('FreshdeskSsoController', 'domain', 'myhelpdesk');

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->equalTo(
                'joe@joe.org authorised Freshdesk Simple SSO logout request'
            ));

        $resp = $this->get('freshdesksso/simplelogout');

        $this->assertSame(302, $resp->getStatusCode());
        $this->assertSame('/', $resp->getHeader('Location'));
    }

    public function testRedirectToAlternatePortal()
    {
        $m = $this->objFromFixture('Member', 'joe');
        $this->session()->inst_set('loggedInAs', $m->ID);

        Config::inst()->update('FreshdeskSsoController', 'hmac_secret', '123');
        Config::inst()->update('FreshdeskSsoController', 'domain', 'myhelpdesk');
        Config::inst()->update('FreshdeskSsoController', 'portal_hostname', 'myhelpdeskportal.myhelpdesk.com');

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->equalTo(
                'joe@joe.org authorised Freshdesk Simple SSO login request'
            ));

        $resp = $this->get('freshdesksso/simplelogin');

        $this->assertSame(302, $resp->getStatusCode());
        $this->assertSame(
            'https://myhelpdeskportal.myhelpdesk.com/login/sso/?name=Joe+Bloggs&email=joe%40joe.org&timestamp=1602324610&hash=3d4b54ff08b74733437e757fa6fb44fa',
            $resp->getHeader('Location')
        );
    }
}
