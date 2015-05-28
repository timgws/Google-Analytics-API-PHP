<?php namespace timgws\GoogleAnalytics;

use timgws\GoogleAnalytics\OAuth\Http;
use timgws\GoogleAnalytics\OAuth\OAuthException;
use timgws\GoogleAnalytics\OAuth\OAuthWeb;
use timgws\GoogleAnalytics\OAuth\OAuthService;

/**
 * Google-Analytics-API
 * Simple class which provides methods to set up OAuth 2.0 with Google and query the Google Analytics API v3 with PHP.
 *
 * There are two possibilities to get the Oauth 2.0 tokens from Google:
 * 1) OAuth 2.0 for Web Applications (end-user involved)
 * 2) OAuth 2.0 for Server to Server Applications (openssl required)
 *
 * Please note that this class does not handle error codes returned from Google. But the the http status code
 * is returned along with the data. You can check for the array-key 'status_code', which should be 200 if everything worked.
 *
 * See the readme on GitHub for instructions and examples how to use the class
 *
 * @author Stefan Wanzenried
 * @copyright Stefan Wanzenried
 * @copyright Tim Groeneveld
 * <www.timg.ws>
 *
 * @version 1.2
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
class API {

    const API_URL = 'https://www.googleapis.com/analytics/v3/data/ga';
    const WEBPROPERTIES_URL = 'https://www.googleapis.com/analytics/v3/management/accounts/~all/webproperties';
    const PROFILES_URL = 'https://www.googleapis.com/analytics/v3/management/accounts/~all/webproperties/~all/profiles';

    public $auth = null;
    protected $accessToken = '';
    protected $accountId = '';
    protected $assoc = true;

    /**
     * Default query parameters
     *
     */
    protected $defaultQueryParams = array ();

    /**
     * Constructor
     *
     * @access public
     * @param String $auth (default: 'web')
     * 'web' for Web-applications with end-users involved,
     * 'service' for service applications (server-to-server)
     * @throws OAuthException
     */
    public function __construct($auth = 'web')
    {
        if (!function_exists('curl_init'))
            throw new OAuthException('The curl extension for PHP is required.');

        $this->auth = ($auth == 'web') ? new OAuthWeb() : new OAuthService();
        $this->defaultQueryParams = array (
            'start-date' => date('Y-m-d', strtotime('-1 month')),
            'end-date' => date('Y-m-d'),
            'metrics' => 'ga:visits',
        );
    }

    /**
     * Set a value inside the class.
     *
     * @param $key
     * @param $value
     * @throws OAuthException
     */
    public function __set($key, $value)
    {
        switch ($key) {
            case 'auth':
                if (($value instanceof OAuth) === false) {
                    throw new OAuthException('auth needs to be a subclass of OAuth');
                }
                $this->auth = $value;
                break;
            case 'defaultQueryParams':
                $this->setDefaultQueryParams($value);
                break;
            default:
                $this->{$key} = $value;
        }
    }

    public function setAccessToken($token)
    {
        $this->accessToken = $token;
    }

    public function setAccountId($id)
    {
        $this->accountId = $id;
    }

    /**
     * Set default query parameters
     * Useful settings: start-date, end-date, max-results
     *
     * @access public
     * @param array () $params Query parameters
     */
    public function setDefaultQueryParams(array $params)
    {
        $params = array_merge($this->defaultQueryParams, $params);
        $this->defaultQueryParams = $params;
    }


    /**
     * Return objects from json_decode instead of arrays
     *
     * @access public
     * @param mixed $bool true to return objects
     */
    public function returnObjects($bool)
    {
        $this->assoc = !$bool;
        $this->auth->returnObjects($bool);
    }


    /**
     * Query the Google Analytics API
     *
     * @access public
     * @param array $params (default: array ()) Query parameters
     * @return array data
     */
    public function query($params = array ())
    {
        return $this->_query($params);
    }


    /**
     * Get all WebProperties
     *
     * @access public
     * @throws OAuthException
     * @return array data
     */
    public function getWebProperties()
    {
        if (!$this->accessToken)
            throw new OAuthException('You must provide an accessToken');

        $data = Http::curl(self::WEBPROPERTIES_URL, array ('access_token' => $this->accessToken));

        return json_decode($data, $this->assoc);
    }


    /**
     * Get all Profiles
     *
     * @access public
     * @throws OAuthException
     * @return array data
     */
    public function getProfiles()
    {
        if (!$this->accessToken)
            throw new OAuthException('You must provide an accessToken');

        $data = Http::curl(self::PROFILES_URL, array ('access_token' => $this->accessToken));

        return json_decode($data, $this->assoc);
    }

    /*****************************************************************************************************************************
     *
     * The following methods implement queries for the most useful statistics, seperated by topics: Audience/Content/Traffic Sources
     *
     *****************************************************************************************************************************/

    /*
     * AUDIENCE
     *
     */

    public function getVisitsByDate($params = array ())
    {
        $defaults = array (
            'metrics' => 'ga:visits',
            'dimensions' => 'ga:date',
        );

        $_params = array_merge($defaults, $params);

        return $this->_query($_params);
    }

    public function getAudienceStatistics($params = array ())
    {
        $defaults = array (
            'metrics' => array (
                'ga:visitors',
                'ga:newVisits',
                'ga:percentNewVisits',
                'ga:visits',
                'ga:bounces',
                'ga:pageviews',
                'ga:visitBounceRate',
                'ga:timeOnSite',
                'ga:avgTimeOnSite'
            )
        );

        $_params = array_merge($defaults, $params);

        return $this->_query($_params);
    }

    public function getVisitsByCountries($params = array ())
    {
        $defaults = array (
            'metrics' => 'ga:visits',
            'dimensions' => 'ga:country',
            'sort' => '-ga:visits',
        );

        $_params = array_merge($defaults, $params);

        return $this->_query($_params);
    }

    public function getVisitsByCities($params = array ())
    {
        $defaults = array (
            'metrics' => 'ga:visits',
            'dimensions' => 'ga:city',
            'sort' => '-ga:visits',
        );

        $_params = array_merge($defaults, $params);

        return $this->_query($_params);
    }

    public function getVisitsByLanguages($params = array ())
    {
        $defaults = array (
            'metrics' => 'ga:visits',
            'dimensions' => 'ga:language',
            'sort' => '-ga:visits',
        );

        $_params = array_merge($defaults, $params);

        return $this->_query($_params);
    }

    public function getVisitsBySystemBrowsers($params = array ())
    {
        $defaults = array (
            'metrics' => 'ga:visits',
            'dimensions' => 'ga:browser',
            'sort' => '-ga:visits',
        );

        $_params = array_merge($defaults, $params);

        return $this->_query($_params);
    }

    public function getVisitsBySystemOs($params = array ())
    {
        $defaults = array (
            'metrics' => 'ga:visits',
            'dimensions' => 'ga:operatingSystem',
            'sort' => '-ga:visits',
        );

        $_params = array_merge($defaults, $params);

        return $this->_query($_params);

    }

    public function getVisitsBySystemResolutions($params = array ())
    {
        $defaults = array (
            'metrics' => 'ga:visits',
            'dimensions' => 'ga:screenResolution',
            'sort' => '-ga:visits',
        );

        $_params = array_merge($defaults, $params);

        return $this->_query($_params);
    }

    public function getVisitsByMobileOs($params = array ())
    {
        $defaults = array (
            'metrics' => 'ga:visits',
            'dimensions' => 'ga:operatingSystem',
            'sort' => '-ga:visits',
            'segment' => 'gaid::-11',
        );

        $_params = array_merge($defaults, $params);

        return $this->_query($_params);
    }

    public function getVisitsByMobileResolutions($params = array ())
    {
        $defaults = array (
            'metrics' => 'ga:visits',
            'dimensions' => 'ga:screenResolution',
            'sort' => '-ga:visits',
            'segment' => 'gaid::-11',
        );

        $_params = array_merge($defaults, $params);

        return $this->_query($_params);
    }

    /*
     * CONTENT
     *
     */
    public function getPageviewsByDate($params = array ())
    {
        $defaults = array (
            'metrics' => 'ga:pageviews',
            'dimensions' => 'ga:date',
        );

        $_params = array_merge($defaults, $params);

        return $this->_query($_params);
    }

    public function getContentStatistics($params = array ())
    {
        $defaults = array (
            'metrics' => 'ga:pageviews,ga:uniquePageviews',
        );

        $_params = array_merge($defaults, $params);

        return $this->_query($_params);
    }

    public function getContentTopPages($params = array ())
    {
        $defaults = array (
            'metrics' => 'ga:pageviews',
            'dimensions' => 'ga:pagePath',
            'sort' => '-ga:pageviews',
        );

        $_params = array_merge($defaults, $params);

        return $this->_query($_params);
    }

    /*
     * TRAFFIC SOURCES
     *
     */
    public function getTrafficSources($params = array ())
    {
        $defaults = array (
            'metrics' => 'ga:visits',
            'dimensions' => 'ga:medium',
        );

        $_params = array_merge($defaults, $params);

        return $this->_query($_params);
    }

    public function getKeywords($params = array ())
    {
        $defaults = array (
            'metrics' => 'ga:visits',
            'dimensions' => 'ga:keyword',
            'sort' => '-ga:visits',
        );

        $_params = array_merge($defaults, $params);

        return $this->_query($_params);
    }

    public function getReferralTraffic($params = array ())
    {
        $defaults = array (
            'metrics' => 'ga:visits',
            'dimensions' => 'ga:source',
            'sort' => '-ga:visits',
        );

        $_params = array_merge($defaults, $params);

        return $this->_query($_params);
    }

    /**
     * Perform a Google Analytics API request.
     *
     * @param array $params
     * @return mixed
     * @throws OAuthException
     */
    protected function _query($params = array ())
    {
        if (!$this->accessToken || !$this->accountId) {
            throw new OAuthException('You must provide the accessToken and an accountId');
        }

        foreach($params as $key => &$value)
        {
            if (is_array($value))
                $value = implode(',', $value);
        }

        $_params = array_merge(
            $this->defaultQueryParams,
            array (
                'access_token' => $this->accessToken,
                'ids' => $this->accountId
            )
        );

        $queryParams = array_merge($_params, $params);
        $data = Http::curl(self::API_URL, $queryParams);

        return json_decode($data, $this->assoc);
    }
}
