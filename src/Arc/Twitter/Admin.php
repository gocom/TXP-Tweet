<?php

/**
 * Admin handler.
 *
 * @todo Remove?
 */

class Arc_Twitter_Admin
{
    /**
     * Constructor.
     */

    public function __construct()
    {
        add_privs('arc_twitter', '1,2,3,4');
        register_tab('extensions', 'arc_twitter', gTxt('arc_twitter'));
        register_callback(array($this, 'pane'), 'arc_twitter');
    }

    /**
     * The pane.
     */

    public function pane()
    {
        
    }
}