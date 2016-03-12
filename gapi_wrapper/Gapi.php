<?php

use timgws\GoogleAnalytics\API as Analytics;
use \Input;
use \Cache;

class Gapi
{
    private $cache_name = 'google_auth_token';
    private $ga;
    private $auth;
    private $result;

    private $report_root_parameters = [];
    private $report_aggregate_metrics = [];

    const IS_DIMENSION = 0;
    const IS_METRIC = 1;

    public function __construct($email, $password, $token = null, $useOauthToken = false)
    {
        /*
         * This is all bad, let's try just a normal log in w/ service account
         */

        $this->ga = new Analytics();
        $this->setGoogleAuthenticationSettings();

        $this->getToken();
    }

    private function setGoogleAuthenticationSettings()
    {
        $this->ga->auth->setClientId('XXXXXXXXXXXX.apps.googleusercontent.com'); // From the APIs console
        $this->ga->auth->setClientSecret('XXXXXXXXXXXXXXXXXXXXXXXX'); // From the APIs console
        $this->ga->auth->setRedirectUri('http://my.website.login.com'); // Url to your app, must match one in the APIs console
    }

    public function requestAccountData()
    {
        /*
        print_r(
            func_get_args()
        );
        */
    }

    public function requestReportData($report_id, $dimensions, $metrics, $sort_metric = null, $filter = null, $start_date = null, $end_date = null, $start_index = 1, $max_results = 30)
    {
        $this->setAuthIfNotAlready();
        $this->ga->setAccountId('ga:'.$this->getGoogleID($report_id));

        $params = array(
            'metrics' => $this->addGaAndMakeString($metrics),
            'dimensions' => $this->addGaAndMakeString($dimensions),
            'sort' => $this->makeSortString($this->addGaAndMakeString($metrics), $sort_metric),
            'max-results' => $max_results,
            'start-date' => $start_date, //Overwrite this from the defaultQueryParams
            'end-date' => $end_date,
            'start-index' => $start_index,
        );

        if ($filter !== null) {
            $filter = $this->processFilter($filter);
            if ($filter !== false) {
                $parameters['filters'] = $filter;
            }
        }

        if ($end_date === null) {
            $params['end-date'] = date('Y-m-d');
        }

        return $this->result = $this->ga->query($params);
    }

    public function getResults()
    {
        return $this->deprecatedWrapper($this->result);
    }

    private function deprecatedWrapper($result)
    {
        //Log::warning("This is a deprecated wrapper. Please upgrade this call to the new Google Analytics API");

        $results = array();
        $row_type = array();
        $report_headers = array();

        $totalMetrics = [];
        $totalMetricsDeprecated = [];

        $i = 0;
        foreach ($result['columnHeaders'] as $header) {
            $report_header = str_replace('ga:', '', $header['name']);

            $row_type[$i] = self::IS_METRIC;
            if ($header['columnType'] == 'DIMENSION') {
                $row_type[$i] = self::IS_DIMENSION;
            }

            $report_headers[$i] = $report_header;
            ++$i;
        }

        if (is_array($result['totalsForAllResults']) && count($result['totalsForAllResults']) > 0) {
            foreach ($result['totalsForAllResults'] as $name => $value) {
                $report_name = str_replace('ga:', '', $name);
                $report_header = $report_name.'All';

                $totalMetrics[$report_name] = $value;
                $totalMetricsDeprecated[$report_header] = $value;
            }

            $this->report_aggregate_metrics = $totalMetrics;
        }

        foreach ($result['rows'] as $row) {
            $i = 0;
            $metrics = $totalMetricsDeprecated;
            $dimensions = array();

            foreach ($row as $value) {
                if ($row_type[$i] == self::IS_METRIC) {
                    $metrics[$report_headers[$i]] = $value;
                } else {
                    $dimensions[$report_headers[$i]] = $value;
                }

                ++$i;
            }

            $results[] = new GapiReportEntry($metrics, $dimensions);
        }

        return $results;
    }

    private function makeSortString($metrics, $sort_metric)
    {
        $parameters = [
            'metrics' => $metrics,
        ];

        if ($sort_metric == null && isset($parameters['metrics'])) {
            $parameters['sort'] = $parameters['metrics'];
        } elseif (is_array($sort_metric)) {
            $sort_metric_string = '';

            foreach ($sort_metric as $sort_metric_value) {
                //Reverse sort - Thanks Nick Sullivan
                if (substr($sort_metric_value, 0, 1) === '-') {
                    $sort_metric_string .= ',-ga:'.substr($sort_metric_value, 1); // Descending
                } else {
                    $sort_metric_string .= ',ga:'.$sort_metric_value; // Ascending
                }
            }

            $parameters['sort'] = substr($sort_metric_string, 1);
        } else {
            if (substr($sort_metric, 0, 1) === '-') {
                $parameters['sort'] = '-ga:'.substr($sort_metric, 1);
            } else {
                $parameters['sort'] = 'ga:'.$sort_metric;
            }
        }

        return $parameters['sort'];
    }

    private function addGaAndMakeString($array)
    {
        if (!is_array($array)) {
            $array = [$array];
        }

        foreach ($array as &$item) {
            $item = 'ga:'.$item;
        }

        return implode(',', $array);
    }

    private function getGoogleID()
    {
        if (Session::has('gaid')) {
            return Session::get('gaid');
        } else {
            $user = Auth::user();
            if (!$user) {
                $this->disabled = true;

                return false;
            }

            $this->company = Auth::user()->getCompany();
            $this->cid = $this->company->id;
            $this->gaId = $this->company->gaid;

            Session::put('gaid', $this->gaId);

            return $this->gaId;
        }
    }

    private function tokenRequiresRefresh($auth)
    {
        $tokenExpires = $auth['expires_in'];
        $tokenCreated = $auth['token_created'];

        if ((time() - $tokenCreated) >= $tokenExpires) {
            return true;
        }

        return false;
    }

    /**
     * Refresh the auth token.
     *
     * @return array
     *
     * @param string $auth authentication array from cache
     *
     * @throws Exception
     * @throws \timgws\GoogleAnalytics\OAuth\OAuthException
     */
    private function refreshAuthToken($auth)
    {
        if (!isset($auth['refresh_token'])) {
            Cache::forget($this->cache_name);
            throw new Exception('The OAuth token is invalid. Refresh the page to attempt again.');
        }

        $old_auth_token = $auth;
        $refreshToken = $auth['refresh_token'];

        $this->ga->auth->setGoogleAuthenticationSettings();
        $auth = $this->ga->auth->refreshAccessToken($refreshToken);

        if ($auth['http_code'] == 200) {
            Log::info('Token refreshed');
            $auth['token_created'] = time();

            Cache::put($this->cache_name, $auth, 24 * 12 * 60);
        } else {
            Log::error('Token refresh failed.', $auth);
        }

        if (!isset($auth['refresh_token']) && isset($old_auth_token['refresh_token'])) {
            $auth['refresh_token'] = $old_auth_token['refresh_token'];
        }

        if (!isset($auth['token_created'])) {
            $auth['token_created'] = time();
        }

        return $auth;
    }

    /**
     * Set a Google token!
     *
     * @param string $code code retrieved from the URL
     *
     * @throws Exception when the OAuth login fails...
     *
     * @return array $auth
     */
    private function getOAuthToken($code)
    {
        $auth = $this->ga->auth->getAccessToken($code);

        // Try to get the AccessToken
        if ($auth['http_code'] == 200) {
            if (isset($auth['access_token']) && isset($auth['refresh_token']) && isset($auth['expires_in'])) {
                $auth['token_created'] = time();

                Cache::put($this->cache_name, $auth, 24 * 12 * 60);

                return $auth;
            } else {
                throw new Exception('Google OAuth login Exception');
            }
        }

        throw new Exception('Google OAuth did not return a 200 code. '.json_encode($auth));
    }

    /**
     * Get the OAuth token. Redirect if needed (throw exception?).
     *
     * @return array
     *
     * @throws Exception
     * @throws \timgws\GoogleAnalytics\OAuth\OAuthException
     */
    private function getToken()
    {
        if (($auth = Cache::get($this->cache_name)) !== null) {
            if ($this->tokenRequiresRefresh($auth)) {
                return $this->refreshAuthToken($auth);
            }

            return $auth;
        } else {
            if (($code = Input::get('code')) !== null) {
                // We are trying to save a new Google OAuth token!
                return $this->getOAuthToken($code);
            } else {
                // We need to get a new OAuth token!
                $url = $this->ga->auth->buildAuthUrl();

                header('Location: '.$url);
                exit;
            }
        }
    }

    private function setAuthIfNotAlready()
    {
        $token = $this->getToken();
        $this->auth = $token;

        $this->ga->setAccessToken($this->auth['access_token']);
    }

    /**
     * Process filter string, clean parameters and convert to Google Analytics
     * compatible format.
     *
     * @param string $filter
     *
     * @return string Compatible filter string
     */
    protected function processFilter($filter)
    {
        $valid_operators = '(!~|=~|==|!=|>|<|>=|<=|=@|!@)';

        $filter = preg_replace('/\s\s+/', ' ', trim($filter)); // Clean duplicate whitespace
        $filter = str_replace(array(',', ';'), array('\,', '\;'), $filter); // Escape Google Analytics reserved characters
        $filter = preg_replace('/(&&\s*|\|\|\s*|^)([a-z]+)(\s*'.$valid_operators.')/i', '$1ga:$2$3', $filter); // Prefix ga: to metrics and dimensions
        $filter = preg_replace('/[\'\"]/i', '', $filter); // Clear invalid quote characters
        $filter = preg_replace(array('/\s*&&\s*/', '/\s*\|\|\s*/', '/\s*'.$valid_operators.'\s*/'), array(';', ',', '$1'), $filter); // Clean up operators

        if (strlen($filter) > 0) {
            return urlencode($filter);
        } else {
            return false;
        }
    }

    /**
     * Case insensitive array_key_exists function, also returns
     * matching key.
     *
     * @param string $key
     * @param array  $search
     *
     * @return string Matching array key
     */
    public static function array_key_exists_nc($key, $search)
    {
        if (array_key_exists($key, $search)) {
            return $key;
        }
        if (!(is_string($key) && is_array($search))) {
            return false;
        }
        $key = strtolower($key);
        foreach ($search as $k => $v) {
            if (strtolower($k) === $key) {
                return $k;
            }
        }

        return false;
    }

    /**
     * Call method to find a matching root parameter or
     * aggregate metric to return.
     *
     * @param $name String name of function called
     * @param $parameters array
     *
     * @return string
     *
     * @throws \Exception if not a valid parameter or aggregate
     *                    metric, or not a 'get' function
     */
    public function __call($name, $parameters)
    {
        if (!preg_match('/^get/', $name)) {
            throw new \InvalidArgumentException('No such function "'.$name.'"');
        }

        $name = preg_replace('/^get/', '', $name);

        $parameter_key = self::array_key_exists_nc($name, $this->report_root_parameters);

        if ($parameter_key) {
            return $this->report_root_parameters[$parameter_key];
        }

        $aggregate_metric_key = self::array_key_exists_nc($name, $this->report_aggregate_metrics);

        if ($aggregate_metric_key) {
            return $this->report_aggregate_metrics[$aggregate_metric_key];
        }

        throw new \InvalidArgumentException('No valid root parameter or aggregate metric called "'.$name.'"');
    }
}
