<?php

namespace timgws\GoogleAnalytics;

/**
 * Abstract Auth class.
 */
abstract class OAuth implements OAuthInterface
{
    /**
     * Different type of OAuth methods!
     */
    const WEB = 1;
    const SERVICE = 2;

    const TOKEN_URL = 'https://accounts.google.com/o/oauth2/token';
    const SCOPE_URL = 'https://www.googleapis.com/auth/analytics.readonly';

    protected $assoc = true;
    protected $clientId = '';

    public function __set($key, $value)
    {
        $this->{$key} = $value;
    }

    public function setClientId($id)
    {
        $this->clientId = $id;
    }

    public function returnObjects($bool)
    {
        $this->assoc = !$bool;
    }
}
