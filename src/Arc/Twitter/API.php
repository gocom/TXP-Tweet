<?php

/**
 * Twitter API Wrapper.
 */

class Arc_Twitter_API extends TijsVerkoyen\Twitter\Twitter
{
    /**
     * Cache duration in seconds.
     *
     * If FALSE disables caching for the next request.
     * If zero (0), the cache never expires.
     *
     * @var int|bool
     */

    protected $cacheDuration = 1800;

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

        if ($method === 'GET' && $body = $this->getCacheStash($url, $parameters))
        {
            $this->cacheDuration = 1800;
            return $body;
        }

        try
        {
            $body = parent::doCall($url, $parameters, $authenticate, $method, $filePath, $expectJSON, $returnHeaders);
        }
        catch (Exception $e)
        {
            if ($method === 'GET' && $body = $this->getCacheStash($url, $parameters, false))
            {
                return $body;
            }

            throw new Exception($e->getMessage());
        }

        $this->cleanCacheStash();

        if ($method === 'GET' && $body)
        {
            $this->setCacheStash($url, $parameters, $body);
            $this->cacheDuration = 1800;
        }

        return $body;
    }

    /**
     * {@inheritdoc}
     */

    public function statusesOEmbed($id = null, $url = null, $maxwidth = null, $hideMedia = null, $hideThread = null, $omitScript = null, $align = null, $related = null, $lang = null)
    {
        $this->cacheDuration = 0;
        return parent::statusesOEmbed($id, $url, $maxwidth, $hideMedia, $hideThread, $omitScript, $align, $related, $lang);
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
        if ($this->cacheDuration === false)
        {
            return;
        }

        $id = md5(json_encode(array($url, $parameters)));
        set_pref('arc_twitter_cache.'.$id, json_encode($body), 'arc_twitter', PREF_HIDDEN);

        if ($this->cacheDuration)
        {
            set_pref('arc_twitter_cachet.'.$id, time() + $this->cacheDuration, 'arc_twitter', PREF_HIDDEN);
        }
    }

    /**
     * Gets cached request body.
     *
     * @param  string     $url          Request resource
     * @param  array      $parameters   Request parameters
     * @param  bool       $checkExpires Whether to check cache expiration date
     * @return array|bool
     */

    protected function getCacheStash($url, $parameters, $checkExpires = true)
    {
        $id = md5(json_encode(array($url, $parameters)));
        $time = get_pref('arc_twitter_cachet.'.$id, false);

        if ($checkExpires === false || $time === false || $time >= time())
        {
            return (array) json_decode(get_pref('arc_twitter_cache.'.$id), true);
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