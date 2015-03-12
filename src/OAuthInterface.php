<?php namespace timgws\GoogleAnalytics\OAuth;

/**
 * OAuth services implement this!
 *
 * Interface OAuthInterface
 * @package timgws\GoogleAnalytics\OAuth
 */
interface OAuthInterface
{

    /**
     * Get the accessToken in exchange with the JWT
     *
     * @access public
     * @param mixed $data (default: null) No data needed in this implementation
     * @return array Array with keys: access_token, expires_in
     */
    public function getAccessToken($data = null)
    {
    }
}