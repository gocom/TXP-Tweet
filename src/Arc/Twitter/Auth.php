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
        register_callback(array($this, 'unlinkAccount'), 'prefs');
        register_callback(array($this, 'unlinkApplication'), 'prefs');
    }

    /**
     * Endpoint responsible to handling responses and redirects.
     */

    public function endpoint()
    {
        $auth = (string) gps('arc_twitter_oauth');
        $method = 'auth'.$auth;

        if (!$auth || get_pref('arc_twitter_account', 1) || !get_pref('arc_twitter_consumer_key') || !get_pref('arc_twitter_consumer_secret') || !method_exists($this, $method))
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
     * authentication web endpoint to let the
     * user to authorize the application to access
     * the account data.
     */

    protected function authAuthorize()
    {
        try
        {
            $response = $this->api->oAuthRequestToken(hu . '?arc_twitter_oauth=AccessToken');
            $this->api->oAuthAuthenticate($response['access_token']);
        }
        catch (Exception $e)
        {
            die($e->getMessage());
        }

        die;
    }

    /**
     * Return callback location for authorization.
     *
     * This method gets the final access token and writes
     * it to the database.
     */

    protected function authAccessToken()
    {
        if (!gps('oauth_token') || get_pref('arc_twitter_account') || get_pref('arc_twitter_access_token') !== gps('oauth_token'))
        {
            return;
        }

        try
        {
            $this->api->oAuthAccessToken(gps('oauth_token'), gps('oauth_verifier'));
            $account = $this->api->accountVerifyCredentials();
        }
        catch (Exception $e)
        {
            die('Twitter API error: ' . $e->getMessage());
        }

        if (isset($account['screen_name']))
        {
            set_pref('arc_twitter_account', $account['screen_name']);
            die(gTxt('arc_twitter_account_linked'));
        }

        die(gTxt('arc_twitter_unable_to_link_account'));
    }

    /**
     * Unlinks account.
     */

    public function unlinkAccount()
    {
        if (!gps('arc_twitter_unlink_account') || !has_privs('prefs.arc_twitter'))
        {
        	return;
        }

        set_pref('arc_twitter_account', '');
        set_pref('arc_twitter_access_token', '');
        set_pref('arc_twitter_access_token_secret', '');
    }

    /**
     * Unlinks application.
     */

    public function unlinkApplication()
    {
        if (!gps('arc_twitter_unlink_application') || !has_privs('prefs.arc_twitter'))
        {
        	return;
        }

        set_pref('arc_twitter_account', '');
        set_pref('arc_twitter_access_token', '');
        set_pref('arc_twitter_access_token_secret', '');
        set_pref('arc_twitter_consumer_key', '');
        set_pref('arc_twitter_consumer_secret', '');
    }
}