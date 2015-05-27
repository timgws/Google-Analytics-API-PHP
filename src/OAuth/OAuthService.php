<?php namespace timgws\GoogleAnalytics\OAuth;

use timgws\GoogleAnalytics\OAuth;

/**
 * Oauth 2.0 for service applications requiring a private key
 * openssl extension for PHP is required!
 * @extends OAuth
 *
 */
class OAuthService extends OAuth {
    const MAX_LIFETIME_SECONDS = 3600;
    const GRANT_TYPE = 'urn:ietf:params:oauth:grant-type:jwt-bearer';

    protected $email = '';
    protected $privateKey = null;
    protected $password = 'notasecret';

    /**
     * Constructor
     *
     * @access public
     * @param string $clientId (default: '') Client-ID of your project from the Google APIs console
     * @param string $email (default: '') E-Mail address of your project from the Google APIs console
     * @param mixed $privateKey (default: null) Path to your private key file (*.p12)
     * @throws OAuthException if `openssl` extension is not enabled/openssl_sign function does not exist
     */
    public function __construct($clientId = '', $email = '', $privateKey = null)
    {
        if (!function_exists('openssl_sign'))
            throw new OAuthException('openssl extension for PHP is needed.');

        $this->clientId = $clientId;
        $this->email = $email;
        $this->privateKey = $privateKey;
    }


    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setPrivateKey($key)
    {
        $this->privateKey = $key;
    }

    /**
     * Get the accessToken in exchange with the JWT
     *
     * @access public
     * @param mixed $data (default: null) No data needed in this implementation
     * @throws OAuthException when missing clientId, email or privateKey.
     * @return array Array with keys: access_token, expires_in
     */
    public function getAccessToken($data = null)
    {
        if (!$this->clientId || !$this->email || !$this->privateKey) {
            throw new OAuthException('You must provide the clientId, email and a path to your private Key');
        }

        $jwt = $this->generateSignedJWT();

        $params = array(
            'grant_type' => self::GRANT_TYPE,
            'assertion' => $jwt,
        );

        $auth = Http::curl(OAuthWeb::TOKEN_URL, $params, true);

        return json_decode($auth, $this->assoc);

    }

    /**
     * Build the JWT encoding JSON strings.
     *
     * @see: https://developers.google.com/accounts/docs/OAuth2ServiceAccount
     * @return array
     */
    private function buildJWTEncodings()
    {
        // Create header, claim and signature
        $header = array(
            'alg' => 'RS256',
            'typ' => 'JWT',
        );

        $currentTime = time();
        $params = array(
            'iss' => $this->email,
            'scope' => OAuthWeb::SCOPE_URL,
            'aud' => OAuthWeb::TOKEN_URL,
            'exp' => $currentTime + self::MAX_LIFETIME_SECONDS,
            'iat' => $currentTime,
        );

        $encodings = array(
            base64_encode(json_encode($header)),
            base64_encode(json_encode($params)),
        );

        return $encodings;
    }

    /**
     * Get certificate store data from a provided pkcs12 file.
     *
     * @return array
     * @throws OAuthException
     */
    private function getCertificateStoreData()
    {
        // Check if a valid privateKey file is provided
        if (!file_exists($this->privateKey) || !is_file($this->privateKey) || !is_readable($this->privateKey)) {
            throw new OAuthException('Private key does not exist, or the permissions on the file are incorrect');
        }

        $certs = array();
        $pkcs12 = file_get_contents($this->privateKey);

        if (!openssl_pkcs12_read($pkcs12, $certs, $this->password)) {
            throw new OAuthException('Could not parse .p12 file');
        }

        return $certs;
    }

    /**
     * Generate and sign a JWT request
     * See: https://developers.google.com/accounts/docs/OAuth2ServiceAccount
     *
     * @access protected
     */
    protected function generateSignedJWT()
    {
        // Retrieve the OpenSSL certificate store.
        $certs = $this->getCertificateStoreData();
        $encodings = $this->buildJWTEncodings();

        // Compute Signature
        $input = implode('.', $encodings);

        if (!isset($certs['pkey'])) {
            throw new OAuthException('Could not find private key in .p12 file');
        }

        $keyId = openssl_pkey_get_private($certs['pkey']);
        if (!openssl_sign($input, $sig, $keyId, 'sha256')) {
            throw new OAuthException('Could not sign data');
        }

        // Generate JWT
        $encodings[] = base64_encode($sig);
        $jwt = implode('.', $encodings);

        return $jwt;

    }

}
