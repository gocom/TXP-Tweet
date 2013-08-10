<?php

/**
 * Share Articles on Twitter.
 */

class Arc_Twitter_Admin_Article extends Arc_Twitter_Admin_Base
{
    /**
     * {@inheritdoc}
     */

    public function __construct()
    {
        register_callback(array($this, 'tweet'), 'article_saved');
        register_callback(array($this, 'tweet'), 'article_posted');
        register_callback(array($this, 'ui'), 'article_ui', 'status');
    }

    /**
     * {@inheritdoc}
     */

    public function tweet($event, $step, $r)
    {
        try
        {
            parent::tweet($event, $step, $r);
        }
        catch (Exception $e)
        {
            // TODO: throw an error alert with announce API.
        }
    }
}