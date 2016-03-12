<?php

namespace timgws\GoogleAnalytics;

/**
 * OAuth services implement this!
 *
 * Interface OAuthInterface
 */
interface OAuthInterface
{
    /**
     * Get the accessToken.
     *
     * @param mixed $data
     *
     * @return array Array with keys: access_token, expires_in
     */
    public function getAccessToken($data = null);
}
