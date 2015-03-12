<?php namespace timgws\GoogleAnalytics\OAuth;

use timgws\GoogleAnalytics\OAuth;

/**
 * Oauth 2.0 for web applications
 * @extends OAuth
 *
 */
class OAuthWeb extends OAuth {

    const AUTH_URL = 'https://accounts.google.com/o/oauth2/auth';
    const REVOKE_URL = 'https://accounts.google.com/o/oauth2/revoke';

    protected $clientSecret = '';
    protected $redirectUri = '';


    /**
     * Constructor
     *
     * @access public
     * @param string $clientId (default: '') Client-ID of your web application from the Google APIs console
     * @param string $clientSecret (default: '') Client-Secret of your web application from the Google APIs console
     * @param string $redirectUri (default: '') Redirect URI to your app - must match with an URL provided in the Google APIs console
     */
    public function __construct($clientId = '', $clientSecret = '', $redirectUri = '')
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
    }

    /**
     * @param $secret
     */
    public function setClientSecret($secret)
    {
        $this->clientSecret = $secret;
    }

    /**
     * @param $uri
     */
    public function setRedirectUri($uri)
    {
        $this->redirectUri = $uri;
    }

    /**
     * Build auth url
     * The user has to login with his Google Account and give your app access to the Analytics API
     *
     * @access public
     * @param array $params Custom parameters
     * @return string The auth login-url
     */
    public function buildAuthUrl($params = array())
    {

        if (!$this->clientId || !$this->redirectUri) {
            throw new OAuthException('You must provide the clientId and a redirectUri');
        }

        $defaults = array(
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => OAuth::SCOPE_URL,
            'access_type' => 'offline',
            'approval_prompt' => 'force',
        );
        $params = array_merge($defaults, $params);
        $url = self::AUTH_URL . '?' . http_build_query($params);

        return $url;

    }

    /**
     * Get the AccessToken in exchange with the code from the auth along with a refreshToken
     *
     * @access public
     * @param mixed $data The code received with GET after auth
     * @return array Array with the following keys: access_token, refresh_token, expires_in
     */
    public function getAccessToken($data = null)
    {
        if (!$this->clientId || !$this->clientSecret || !$this->redirectUri) {
            throw new OAuthException('You must provide the clientId, clientSecret and a redirectUri');
        }

        $params = array(
            'code' => $data,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        );

        $auth = Http::curl(OAuth::TOKEN_URL, $params, true);

        return json_decode($auth, $this->assoc);
    }

    /**
     * Get a new accessToken with the refreshToken
     *
     * @access public
     * @param mixed $refreshToken The refreshToken
     * @return array Array with the following keys: access_token, expires_in
     */
    public function refreshAccessToken($refreshToken)
    {

        if (!$this->clientId || !$this->clientSecret) {
            throw new OAuthException('You must provide the clientId and clientSecret');
        }

        $params = array(
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        );

        $auth = Http::curl(GoogleOauth::TOKEN_URL, $params, true);

        return json_decode($auth, $this->assoc);

    }

    /**
     * Revoke access
     *
     * @access public
     * @param mixed $token accessToken or refreshToken
     */
    public function revokeAccess($token)
    {

        $params = array('token' => $token);
        $data = Http::curl(self::REVOKE_URL, $params);

        return json_decode($data, $this->assoc);
    }

}
