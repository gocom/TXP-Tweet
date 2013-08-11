<?php

class Arc_Twitter
{
    /**
     * Constructor.
     */

    public function __construct()
    {
        new Arc_Twitter_Install();
        new Arc_Twitter_Admin();
        new Arc_Twitter_Admin_Article();
        new Arc_Twitter_Auth();

        Textpattern_Tag_Registry::register(array('Arc_Twitter_Tag_Button', 'follow'), 'arc_twitter_follow');
        Textpattern_Tag_Registry::register(array('Arc_Twitter_Tag_Button', 'share'), 'arc_twitter_share');
    }
}

new Arc_Twitter();