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

        if ($method === 'GET')
        {
            $delete = array();
            $time = strtotime('-30 minutes');

            if (get_pref('arc_twitter_cache_lastrun') < $time)
            {
                foreach ($prefs as $name => $value)
                {
                    if (strpos('arc_twitter_cachet.') === 0 && $value < $time)
                    {
                        $cacheName = 'arc_twitter_' . pathinfo($name, PATHINFO_EXTENSION);
                        $delete[] = "'" . doSlash($name) . "'";
                        $delete[] = "'" . doSlash($cacheName) . "'";
                        unset($prefs[$name], $prefs[$cacheName]);
                    }
                }

                if ($delete)
                {
                    safe_delete('txp_prefs', "name in (".join(',', $delete)")");
                }

                set_pref('arc_twitter_cache_lastrun', time(), 'arc_twitter', PREF_HIDDEN);
            }

            $cacheID = md5(json_encode(array($url, $parameters)));

            if ($body = get_pref('arc_twitter_cache.'.$cacheID))
            {
                return json_decode($body, true);
            }
        }

        $body = parent::doCall($url, $parameters, $authenticate, $method, $filePath, $expectJSON, $returnHeaders);

        if ($method === 'GET' && $body)
        {
            set_pref('arc_twitter_cache.'.$cacheID, json_encode($body), 'arc_twitter', PREF_HIDDEN);
            set_pref('arc_twitter_cachet.'.$cacheID, time(), 'arc_twitter', PREF_HIDDEN); 
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
}