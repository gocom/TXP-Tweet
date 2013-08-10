<?php

/**
 * Installer.
 */

class Arc_Twitter_Install
{
    /**
     * An array of plugin preference strings.
     *
     * @var array
     */

    protected $prefs = array(
        'user'                => array('text_input', ''),
        'prefix'              => array('text_input', 'Just posted:'),
        'suffix'              => array('text_input', ''),
        'cache_dir'           => array('text_input', ''), // TODO: remove?
        'tweet_default'       => array('yesnoradio', 1),
        'access_token'        => array('text_input', ''),
        'access_token_secret' => array('text_input', ''),
        'consumer_key'        => array('text_input', ''),
        'consumer_secret'     => array('text_input', ''),
    );

    /**
     * Constructor.
     */

    public function __construct()
    {
        add_privs('prefs.arc_twitter', '1');
        register_callback(array($this, 'install'), 'plugin_lifecycle.arc_twitter', 'installed');
        register_callback(array($this, 'uninstall'), 'plugin_lifecycle.arc_twitter', 'deleted');
    }

    /**
     * Installer.
     */

    public function install()
    {
        safe_query(
            "CREATE TABLE IF NOT EXISTS ".safe_pfx('arc_twitter')." (
                id INTEGER(11) AUTO_INCREMENT PRIMARY KEY,
                article INTEGER(11) NOT NULL default 0,
                tweet_id VARCHAR(255) NOT NULL default '',
                status VARCHAR(140) NOT NULL default '',
                url VARCHAR(255) NOT NULL default ''
            ) PACK_KEYS=1 AUTO_INCREMENT=1 CHARSET=utf8"
        );

        $position = 1;

        foreach ($this->prefs as $name => $pref)
        {
            if (($name = 'arc_twitter_' . $name) && get_pref($name, false) === false)
            {
                set_pref($name, $pref[1], 'arc_twitter', $position, $pref[0]);
            }

            $position++;
        }
    }

    /**
     * Uninstaller.
     */

    public function uninstall()
    {
        safe_query("DROP TABLE IF EXISTS ".safe_pfx('arc_twitter'));
        safe_delete('txp_prefs', "event = 'arc_twitter'");
    }
}