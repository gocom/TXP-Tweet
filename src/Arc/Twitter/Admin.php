<?php

/**
 * Admin handler.
 */

class Arc_Twitter_Admin
{
    public function __construct()
    {
        add_privs('arc_admin_twitter', '1,2,3,4');
        register_tab('extensions', 'arc_twitter', gTxt('arc_twitter'));
        register_callback(array($this, 'pane'), 'arc_twitter');
    }

    public function pane()
    {
        
    }
}