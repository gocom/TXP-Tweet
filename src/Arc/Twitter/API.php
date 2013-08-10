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
     *
     * @todo Extend with caching layer
     */

    protected function doCall($url, array $parameters = null, $authenticate = false, $method = 'GET', $filePath = null, $expectJSON = true, $returnHeaders = false)
    {
        return parent::doCall($url, $parameters, $authenticate, $method, $filePath, $expectJSON, $returnHeaders);
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