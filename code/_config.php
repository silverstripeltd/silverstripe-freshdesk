<?php

if (defined('FRESHDESK_HMAC_SECRET')) {
    Config::inst()->update('FreshdeskSsoController', 'hmac_secret', FRESHDESK_HMAC_SECRET);
}
if (defined('FRESHDESK_TOKEN')) {
    Config::inst()->update('FreshdeskService', 'token', FRESHDESK_TOKEN);
}
if (defined('FRESHDESK_DOMAIN')) {
    Config::inst()->update('FreshdeskSsoController', 'domain', FRESHDESK_DOMAIN);
    Config::inst()->update('FreshdeskService', 'domain', FRESHDESK_DOMAIN);
}
if (defined('FRESHDESK_PORTAL_HOSTNAME')) {
    Config::inst()->update('FreshdeskSsoController', 'portal_hostname', FRESHDESK_PORTAL_HOSTNAME);
}
