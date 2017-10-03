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
        // Route different Portals - single instance of Freshdesk
        $portalUrl = $this->request->getVar('host_url');
        $freshdeskPortalRedirects = Config::inst()->get('FreshdeskSSO', 'freshdeskPortalRedirects');

        if (array_key_exists($portalUrl, $freshdeskPortalRedirects)) {
            $this->redirect($freshdeskPortalRedirects[$portalUrl]);
        }

        $currentMember = \Member::currentUser();
        if (!$currentMember || !$currentMember->exists()) {
            return \Security::permissionFailure();
        }

        $this->redirect($this->getSSOUrl($currentMember->getName(),$currentMember->Email));
    }

    private function getSSOUrl($strName, $strEmail)
    {
        $timestamp = time();
        $toBeHashed = $strName . FRESHDESK_HMAC_SECRET . $strEmail . $timestamp;
        $hash = hash_hmac('md5', $toBeHashed, FRESHDESK_HMAC_SECRET);
        return 'http://'.FRESHDESK_PORTAL_BASEURL.'/login/sso/?name='.urlencode($strName).'&email='.urlencode($strEmail).'&timestamp='.$timestamp.'&hash='.$hash;
    }

    public function simpleLogout()
    {
        $portalUrl = $this->request->getVar('host_url');
        $currentMember = \Member::currentUser();
        if ($currentMember) {
            $currentMember->logOut();            
        }
        $this->redirect('home/');
    }
}
