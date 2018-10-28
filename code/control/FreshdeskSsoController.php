<?php

class FreshdeskSsoController extends Controller
{
    /**
     * @var FreshdeskAuditLogger
     */
    public $logger;

    /**
     * @var array
     */
    private static $dependencies = [
        'logger' => '%$FreshdeskAuditLogger',
    ];

    /**
     * @var array
     */
    private static $allowed_actions = [
        'simplelogin',
        'simplelogout',
    ];

    /**
     * @var null|string
     * @config
     */
    private static $hmac_secret;

    /**
     * @var null|string
     * @config
     */
    private static $domain;

    /**
     * @var null|string
     * @config
     */
    private static $portal_hostname;

    /**
     * @var array
     */
    private static $freshdeskPortalRedirects = [];

    public function simplelogin()
    {
        if (!$this->enabled()) {
            return $this->redirect('/');
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

            $this->logger->info(sprintf('%s authorised Freshdesk Simple SSO login request', $member->Email));

            $this->redirect($this->getSSOUrl($member->getName(), $member->Email));
        }
    }

    public function simplelogout()
    {
        if (!$this->enabled()) {
            return $this->redirect('/');
        }

        // Route different Portals - single instance of Freshdesk
        $portalUrl = $this->request->getVar('host_url');
        $freshdeskPortalRedirects = $this->config()->freshdeskPortalRedirects;

        if (array_key_exists($portalUrl, $freshdeskPortalRedirects)) {
            $this->redirect($freshdeskPortalRedirects[$portalUrl].'/freshdesksso/simplelogout');
        } else {
            $member = Member::currentUser();
            if ($member) {
                $this->logger->info(sprintf('%s authorised Freshdesk Simple SSO logout request', $member->Email));
                $member->logOut();
            }

            $this->redirect('/');
        }
    }

    private function enabled()
    {
        if ($this->config()->hmac_secret && ($this->config()->domain || $this->config()->portal_hostname)) {
            return true;
        }

        return false;
    }

    private function getSSOUrl($name, $email)
    {
        $timestamp = SS_Datetime::now()->Format('U');
        $value = sprintf('%s%s%s%s', $name, $this->config()->hmac_secret, $email, $timestamp);
        $hash = hash_hmac('md5', $value, $this->config()->hmac_secret);
        $host = $this->config()->portal_hostname ?: sprintf('%s.freshdesk.com', $this->config()->domain);

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
