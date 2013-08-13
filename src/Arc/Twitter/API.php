<?php

/**
 * Twitter API Wrapper.
 */

class Arc_Twitter_API extends TijsVerkoyen\Twitter\Twitter
{
    /**
     * {@inheritdoc}
     */

    public function __construct($consumerKey, $consumerSecret)
    {
        if ($consumerKey === null)
        {
            $consumerKey = get_pref('arc_twitter_consumer_key');
            $consumerSecret = get_pref('arc_twitter_consumer_secret');
        }

        if (!$consumerKey || !$consumerSecret)
        {
            throw new Exception('Please provide Twitter application consumer key and secret before proceeding.');
        }

        parent::__construct($consumerKey, $consumerSecret);
        $this->oAuthToken = get_pref('arc_twitter_access_token');
        $this->oAuthTokenSecret = get_pref('arc_twitter_access_token_secret');
    }

    /**
     * {@inheritdoc}
     */

    protected function doCall($url, array $parameters = null, $authenticate = false, $method = 'GET', $filePath = null, $expectJSON = true, $returnHeaders = false)
    {
        global $prefs;

        if ($method === 'GET' && $body = $this->getCacheStash())
        {
            return $body;
        }

        $body = parent::doCall($url, $parameters, $authenticate, $method, $filePath, $expectJSON, $returnHeaders);

        $this->cleanCacheStash();

        if ($method === 'GET' && $body)
        {
            $this->setCacheStash($url, $parameters, $body);
        }

        return $body;
    }

    /**
     * {@inheritdoc}
     */

    public function setOAuthToken($token)
    {
        set_pref('arc_twitter_access_token', $token);
        return parent::setOAuthToken();
    }

    /**
     * {@inheritdoc}
     */

    public function setOAuthTokenSecret($secret)
    {
        set_pref('arc_twitter_access_token_secret', $secret);
        return parent::setOAuthTokenSecret();
    }

    /**
     * Sets item to the cache.
     *
     * @param string $url        Request resource
     * @param array  $parameters Request parameters
     * @param array  $body       The response body
     */

    protected function setCacheStash($url, $parameters, $body)
    {
        $id = md5(json_encode(array($url, $parameters)));
        set_pref('arc_twitter_cache.'.$id, json_encode($body), 'arc_twitter', PREF_HIDDEN);
        set_pref('arc_twitter_cachet.'.$id, strtotime('+30 minutes'), 'arc_twitter', PREF_HIDDEN);
    }

    /**
     * Gets cached request body.
     *
     * @param  string     $url        Request resource
     * @param  array      $parameters Request parameters
     * @return array|bool
     */

    protected function getCacheStash($url, $parameters)
    {
        $id = md5(json_encode(array($url, $parameters)));

        if ($body = get_pref('arc_twitter_cache.'.$id) && get_pref('arc_twitter_cachet.'.$id) >= time())
        {
            return (array) json_decode($body, true);
        }

        return false;
    }

    /**
     * Cleans cache from old results.
     *
     * Checks cache every 30 minutes at most, and deletes
     * any cache items which expiration date is past.
     */

    protected function cleanCacheStash()
    {
        global $prefs;

        if (get_pref('arc_twitter_cache_lastrun', 0) < strtotime('-30 minutes'))
        {
            $delete = array();
            $time = time();

            foreach ($prefs as $name => $value)
            {
                if (strpos('arc_twitter_cachet.') === 0 && $value < $time)
                {
                    $cacheName = 'arc_twitter_' . pathinfo($name, PATHINFO_EXTENSION);
                    $delete[] = $name;
                    $delete[] = $cacheName;
                }
            }

            if (!$delete || safe_delete('txp_prefs', "name in (".implode(',', quote_list($delete))")"))
            {
                foreach ($delete as $name)
                {
                    unset($prefs[$name]);
                }

                set_pref('arc_twitter_cache_lastrun', time(), 'arc_twitter', PREF_HIDDEN);
            }
        }
    }
}