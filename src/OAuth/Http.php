<?php

namespace timgws\GoogleAnalytics\OAuth;

class Http
{
    /**
     * Send http requests with curl.
     *
     * @static
     *
     * @param mixed $url    The url to send data
     * @param array $params (default: array()) Array with key/value pairs to send
     * @param bool  $post   (default: false) True when sending with POST
     *
     * @return mixed
     */
    public static function curl($url, $params = array(), $post = false)
    {
        return self::usecURL($url, $params, $post);
    }

    public static function createCurlObject($url, $params, $post)
    {
        $curl = curl_init($url);

        $options = [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPAUTH => CURLAUTH_ANY,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_USERAGENT => 'Google Analytics API (timgws/Google-Analytics-API)',
        ];

        if ($post) {
            $options +=
                [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $params,
                ];
        }

        curl_setopt_array($curl, $options);

        return $curl;
    }

    /**
     * Send http requests with curl.
     *
     * @static
     *
     * @param mixed $url    The url to send data
     * @param array $params (default: array()) Array with key/value pairs to send
     * @param bool  $post   (default: false) True when sending with POST
     *
     * @return string $data the data from the cURL request.
     */
    public static function usecURL($url, $params = array(), $post = false)
    {
        if (empty($url)) {
            return false;
        }

        if (!$post && !empty($params)) {
            $url = $url.'?'.http_build_query($params);
        }

        $curl = self::createCurlObject($url, $params, $post);

        $data = curl_exec($curl);
        $http_code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // Add the status code to the json data, useful for error-checking
        $data = preg_replace('/^{/', '{"http_code":'.$http_code.',', $data);
        curl_close($curl);

        return $data;
    }
}
