<?php

class FreshdeskSSO extends Controller
{

    public static $allowed_actions = [
        'simple',
    ];

    public function simple()
    {
        $currentMember = \Member::currentUser();
        if (!$currentMember || !$currentMember->exists()) {
            return \Security::permissionFailure();
        }

        // Route different Portals - single instance of Freshdesk
        $portalUrl = $this->request->getVar('host_url');
        if ($portalUrl != 'cwptest.silverstripe.com') {
            $this->redirect('https://silverstripesupport.freshdesk.com/login/normal');
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

}
