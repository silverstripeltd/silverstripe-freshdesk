<?php

namespace SilverStripe\Freshdesk;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;
use SilverStripe\Framework\Injector\Factory;

class LogFactory implements Factory
{
    public function create($service, array $params = [])
    {
        $logger = new Logger('freshdesk');
        $syslog = new SyslogHandler('SilverStripe_freshdesk', LOG_AUTH, Logger::DEBUG);
        $syslog->pushProcessor(new WebProcessor($_SERVER, [
            'url' => 'REQUEST_URI',
            'http_method' => 'REQUEST_METHOD',
            'server' => 'SERVER_NAME',
            'referrer' => 'HTTP_REFERER',
        ]));
        $formatter = new LineFormatter('%level_name%: %message% %context% %extra%');
        $syslog->setFormatter($formatter);
        $logger->pushHandler($syslog);

        return $logger;
    }
}
