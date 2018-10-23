<?php

class FreshdeskSsoController extends Controller
{
    private static $allowed_actions = [
        'simplelogin',
        'simplelogout',
    ];

    private static $freshdeskPortalRedirects = [];

    public function simplelogin()
    {
        if (!$this->enabled()) {
            $this->redirect('/');
        }

        // Route different Portals - single instance of Freshdesk
        $portalUrl = $this->request->getVar('host_url');
        $freshdeskPortalRedirects = $this->config()->freshdeskPortalRedirects;

        if (array_key_exists($portalUrl, $freshdeskPortalRedirects)) {
            $this->redirect($freshdeskPortalRedirects[$portalUrl].'/freshdesksso/simplelogin?host_url='.$portalUrl);
        } else {
            $member = Member::currentUser();
            if (!$member || !$member->exists()) {
                return Security::permissionFailure();
            }
            $this->redirect($this->getSSOUrl($member->getName(), $member->Email));
        }
    }

    public function simplelogout()
    {
        if (!$this->enabled()) {
            $this->redirect('/');
        }

        // Route different Portals - single instance of Freshdesk
        $portalUrl = $this->request->getVar('host_url');
        $freshdeskPortalRedirects = $this->config()->freshdeskPortalRedirects;

        if (array_key_exists($portalUrl, $freshdeskPortalRedirects)) {
            $this->redirect($freshdeskPortalRedirects[$portalUrl].'/freshdesksso/simplelogout');
        } else {
            $member = Member::currentUser();
            if ($member) {
                $member->logOut();
            }
            $this->redirect('/');
        }
    }

    private function enabled()
    {
        if (defined('FRESHDESK_HMAC_SECRET') && (defined('FRESHDESK_DOMAIN') || defined('FRESHDESK_PORTAL_HOSTNAME'))) {
            return true;
        }

        return false;
    }

    private function getSSOUrl($name, $email)
    {
        $timestamp = time();
        $toBeHashed = $name.FRESHDESK_HMAC_SECRET.$email.$timestamp;
        $hash = hash_hmac('md5', $toBeHashed, FRESHDESK_HMAC_SECRET);
        $host = defined('FRESHDESK_PORTAL_HOSTNAME')
            ? FRESHDESK_PORTAL_HOSTNAME
            : sprintf('%s.freshdesk.com', FRESHDESK_DOMAIN);

        return sprintf(
            'https://%s/login/sso/?name=%s&email=%s&timestamp=%s&hash=%s',
            $host,
            urlencode($name),
            urlencode($email),
            $timestamp,
            $hash
        );
    }
}
