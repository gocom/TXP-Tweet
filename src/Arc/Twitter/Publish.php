<?php

/**
 * Auto-publish tweets.
 */

class Arc_Twitter_Publish
{
    /**
     * Constructor.
     */

    public function __construct()
    {
        register_callback(array($this, 'tweet'), 'article_saved');
        register_callback(array($this, 'tweet'), 'article_posted');
        register_callback(array($this, 'ui'), 'article_ui', 'status');
    }

    /**
     * Publishes the article on Twitter.
     *
     * @todo Shorten the URL
     * @todo Catch Exceptions thrown by Twitter API
     */

    public function tweet($event, $step, $r)
    {
        extract(psa(array(
            'arc_twitter_prefix',
            'arc_twitter_suffix',
            'arc_twitter_tweet',
        )));

        // Title is escaped for SQL in 4.5.x. Due to this bug, we need to pull it from the DB.

        if (!$arc_twitter_tweet || !($url = permlinkurl_id($r['ID'])) || !($title = trim(safe_field('Title', 'textpattern', 'ID = '.intval($r['ID']).' and Status = '.STATUS_LIVE))))
        {
            return;
        }

        if (safe_row('arc_twitter', "article = ".intval($r['ID'])))
        {
            return;
        }

        $status = array(
            $arc_twitter_prefix,
            $title,
            $url,
            $arc_twitter_suffix,
        );

        if (($over = strlen(trim(join(' ', $status))) - 140) && $over > 0)
        {
            $status[1] = substr($status[1], 0, $over * -1);
        }

        $status = trim(join(' ', $status));

        $twitter = new Arc_Twitter_API();
        $result = $twitter->statusesUpdate($status);

        if ($result && $result['id'])
        {
            safe_insert(
                'arc_twitter',
                "article = ".intval($r['ID']).",
                status_id = '".doSlash($result['id'])."',
                status = '".doSlash($status)."',
                url = '".doSlash($url)."'"
            );
        }
    }

    /**
     * Tweet options group on Write panel.
     */

    public function ui($event, $step, $default, $rs)
    {
        return $default . wrapRegion(
            'arc_twitter_tweet',

            graf(
                checkbox('arc_twitter_tweet', 1, '', '', 'reset_time').
                tag(gTxt('arc_twitter_tweet_this'), 'label', array('for' => 'reset_time'))
            ).

            graf(
                tag(gTxt('arc_twitter_prefix'), 'label', array('for' => 'arc_twitter_prefix')).br.
                fInput('text', 'arc_twitter_prefix', '', '', '', '', INPUT_REGULAR, '', 'arc_twitter_prefix')
            ).

            graf(
                tag(gTxt('arc_twitter_suffix'), 'label', array('for' => 'arc_twitter_suffix')).br.
                fInput('text', 'arc_twitter_suffix', '', '', '', '', INPUT_REGULAR, '', 'arc_twitter_suffix')
            ),

            '',

            gTxt('arc_twitter')
        );
    }
}

new Arc_Twitter_Publish();