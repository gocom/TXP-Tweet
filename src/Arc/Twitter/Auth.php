<?php

/**
 * Authorizes the consumer application.
 */

class Arc_Twitter_Auth
{
    /**
     * The API.
     *
     * @var Arc_Twitter_API
     */

    protected $api;

    /**
     * Constructor.
     */

    public function __construct()
    {
        register_callback(array($this, 'endpoint'), 'textpattern');
    }

    /**
     * Endpoint responsible to handling responses and redirects.
     *
     * @todo Should use admin-side. The problem is that we don't know the admin location.
     */

    public function endpoint()
    {
        $auth = (string) gps('arc_twitter_oauth');
        $method = 'auth'.$auth;

        if (!$auth || get_pref('arc_twitter_access_token') || !get_pref('arc_twitter_consumer_key') || !get_pref('arc_twitter_consumer_secret') || !method_exists($this, $method))
        {
            return;
        }

        $this->api = new Arc_Twitter_API(null, null);
        $this->$method();
    }

    /**
     * Authorizes the consumer application.
     *
     * This method redirects the user to Twitter's
     * authentication web endpoint.
     */

    protected function authAuthorize()
    {
    }

    /**
     * Return callback location for authorization.
     *
     * This method gets the access token and writes
     * it to the database.
     */

    protected function authAccesstoken()
    {
    }
}