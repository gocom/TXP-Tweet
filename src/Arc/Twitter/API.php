<?php

/**
 * Twitter API Wrapper.
 *
 * @todo oAuth loopback
 */

class Arc_Twitter_API extends TijsVerkoyen\Twitter\Twitter
{
    /**
     * {@inheritdoc}
     */

    public function __construct($consumerKey, $consumerSecret)
    {
        parent::__construct(
            get_pref('arc_twitter_consumer_key'),
            get_pref('arc_twitter_consumer_secret')
        );

        $this->setOAuthToken(get_pref('arc_twitter_access_token'));
        $this->setOAuthTokenSecret(get_pref('arc_twitter_access_token_secret'));
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
}