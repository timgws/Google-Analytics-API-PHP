<?php namespace timgws\GoogleAnalytics;

use timgws\GoogleAnalytics\OAuth\OAuthInterface;

/**
 * Abstract Auth class
 *
 */
abstract class OAuth implements OAuthInterface {

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
