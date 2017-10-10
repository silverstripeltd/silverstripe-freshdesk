<?php

class FreshdeskSSO extends Controller
{
    public static $allowed_actions = [
        'simpleLogin',
        'simpleLogout',
    ];

    private static $freshdeskPortalRedirects = [];

    public function simpleLogin()
    {
        if (!defined('FRESHDESK_HMAC_SECRET') || !defined('FRESHDESK_PORTAL_BASEURL')) {
            $this->redirect('/');
        }

        // Route different Portals - single instance of Freshdesk
        $portalUrl = $this->request->getVar('host_url');
        $freshdeskPortalRedirects = Config::inst()->get('FreshdeskSSO', 'freshdeskPortalRedirects');

        if (array_key_exists($portalUrl, $freshdeskPortalRedirects)) {
            $this->redirect($freshdeskPortalRedirects[$portalUrl].'/freshdesksso/simpleLogin?host_url='.$portalUrl);
        } else {
            $currentMember = \Member::currentUser();
            if (!$currentMember || !$currentMember->exists()) {
                return \Security::permissionFailure();
            }
            $this->redirect($this->getSSOUrl($currentMember->getName(), $currentMember->Email));
        }
    }

    public function simpleLogout()
    {
        // Route different Portals - single instance of Freshdesk
        $portalUrl = $this->request->getVar('host_url');
        $freshdeskPortalRedirects = Config::inst()->get('FreshdeskSSO', 'freshdeskPortalRedirects');

        if (array_key_exists($portalUrl, $freshdeskPortalRedirects)) {
            $this->redirect($freshdeskPortalRedirects[$portalUrl].'/freshdesksso/simpleLogout');
        } else {
            $currentMember = \Member::currentUser();
            if ($currentMember) {
                $currentMember->logOut();
            }
            $this->redirect('home/');
        }
    }

    private function getSSOUrl($strName, $strEmail)
    {
        $timestamp = time();
        $toBeHashed = $strName.FRESHDESK_HMAC_SECRET.$strEmail.$timestamp;
        $hash = hash_hmac('md5', $toBeHashed, FRESHDESK_HMAC_SECRET);

        return sprintf('http://%s/login/sso/?name=%s&email=%s&timestamp=%s&hash=%s', FRESHDESK_PORTAL_BASEURL, urlencode($strName), urlencode($strEmail), $timestamp, $hash);
    }
}
