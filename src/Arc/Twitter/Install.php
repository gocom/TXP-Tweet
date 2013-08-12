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
        'message'             => array('text_input', '{title} {url}', PREF_PLUGIN),
        'tweet'               => array('yesnoRadio', 1, PREF_PLUGIN),
        'consumer_key'        => array('Arc_Twitter_Pref_Fields->key', '', PREF_PLUGIN),
        'consumer_secret'     => array('Arc_Twitter_Pref_Fields->key', '', PREF_PLUGIN),
        'access_token'        => array('Arc_Twitter_Pref_Fields->token', '', PREF_PLUGIN),
        'access_token_secret' => array('text_input', '', PREF_HIDDEN),
        'account'             => array('text_input', '', PREF_HIDDEN),
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
                type VARCHAR(255) NOT NULL default '',
                item INTEGER(11) NOT NULL default 0,
                status_id VARCHAR(255) NOT NULL default '',
                url VARCHAR(255) NOT NULL default ''
            ) PACK_KEYS=1 AUTO_INCREMENT=1 CHARSET=utf8"
        );

        $position = 1;

        foreach ($this->prefs as $name => $pref)
        {
            if (($name = 'arc_twitter_' . $name) && get_pref($name, false) === false)
            {
                set_pref($name, $pref[1], 'arc_twitter', $pref[2], $pref[0], $position);
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