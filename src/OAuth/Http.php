<?php namespace timgws\GoogleAnalytics\OAuth;

use GuzzleHttp\Client as HttpClient;

class Http {

    /**
     * Send http requests with curl
     *
     * @access public
     * @static
     * @param mixed $url The url to send data
     * @param array $params (default: array()) Array with key/value pairs to send
     * @param bool $post (default: false) True when sending with POST
     *
     * @todo: THIS IS BROKEN!
     * I believe it might have to do with CURLOPT_HTTPAUTH
     */
    public static function curl($url, $params=array(), $post=false)
    {
        return self::usecURL($url, $params, $post);
        //return self::useGuzzle($url, $params, $post);
    }

    /**
     * Send http requests with curl
     *
     * @access public
     * @static
     * @param mixed $url The url to send data
     * @param array $params (default: array()) Array with key/value pairs to send
     * @param bool $post (default: false) True when sending with POST
     *
     */
    public static function usecURL($url, $params=array(), $post=false)
    {
        if (empty($url))
            return false;

        if (!$post && !empty($params)) {
            $url = $url . "?" . http_build_query($params);
        }

        $curl = curl_init($url);

        if ($post) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        }

        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        $http_code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // Add the status code to the json data, useful for error-checking
        $data = preg_replace('/^{/', '{"http_code":' . $http_code . ',', $data);
        curl_close($curl);

        return $data;
    }

	/**
     * Send http requests with curl
     *
     * @access public
     * @static
     * @param mixed $url The url to send data
     * @param array $params (default: array()) Array with key/value pairs to send
     * @param bool $post (default: false) True when sending with POST
     *
     * @todo: THIS IS BROKEN!
     * I believe it might have to do with CURLOPT_HTTPAUTH
     */
	public static function useGuzzle($url, $params=array(), $post=false)
    {
        if (empty($url)) {
            return false;
        }

        $client = new HttpClient();

        $options = null;
        $verb = 'GET';
        if (is_array($params) && count($params) !== 0) {
            $key = 'query';
            if ($post) {
                $verb = 'POST';
                $key = 'body';
            }

            $options = array(
                $key => $params
            );
        }

        $options['debug'] = true;

        //$request = $client->createRequest($verb, $url, $options);

        if ($post) {
            $response = $client->post($url, $options);
        } else {
            $response = $client->get($url, $options);
        }

        // Add the status code to the json data, useful for error-checking
        $http_code = $response->getStatusCode();

        $body = $response->getBody();
        $data = (string)$body;
        $data = preg_replace('/^{/', '{"http_code":'.$http_code.',', $data);

        // Finally, return the response to the caller.
        return $data;
    }
}