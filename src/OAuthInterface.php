<?php namespace timgws\GoogleAnalytics;

/**
 * OAuth services implement this!
 *
 * Interface OAuthInterface
 * @package timgws\GoogleAnalytics\OAuth
 */
interface OAuthInterface
{

    /**
     * Get the accessToken
     *
     * @access public
     * @param mixed $data
     * @return array Array with keys: access_token, expires_in
     */
    public function getAccessToken($data = null);
}