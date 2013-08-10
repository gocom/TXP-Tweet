<?php

/**
 * Preference fields.
 */

class Arc_Twitter_Pref_Fields
{
    /**
     * Options controller for access token.
     *
     * @return string HTML
     */

    public function token()
    {
        global $event;

        if (!get_pref('arc_twitter_consumer_key', '', true) || !get_pref('arc_twitter_consumer_secret', '', true))
        {
            return
                n.span(gTxt('arc_twitter_authorize'), array(
                    'class' => 'navlink-disabled',
                )).
                n.span(gTxt('arc_twitter_set_keys', array('{save}' => gTxt('save'))), array(
                    'class' => 'information',
                ));
        }

        if (get_pref('arc_twitter_access_token'))
        {
            return
                n.href(gTxt('arc_twitter_unlink'), array(
                    'event'              => $event,
                    'arc_twitter_unlink' => 1,
                ), array('class' => 'navlink'));
        }

        return 
            n.href(gTxt('arc_twitter_authorize'), hu.'?arc_twitter_oauth=Authorize', array(
                'class' => 'navlink',
            )).
            n.href(gTxt('arc_twitter_unlink'), array(
                'event'              => $event,
                'arc_twitter_unlink' => 1,
            ));
    }

    /**
     * Options controller for the application key.
     *
     * @param  string $name  Field name
     * @param  string $value Current value
     * @return string HTML
     */

    public function key($name, $value)
    {
        if ($value !== '')
        {
            $value = str_pad('', strlen($value), '*');
            $value = text_input($name.'_null', $value, INPUT_REGULAR);
            return str_replace('<input', '<input disabled="disabled"', $value);
        }

        return text_input($name, $value, INPUT_REGULAR);
    }
}