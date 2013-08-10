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

        foreach (array(1, 2) as $iteration)
        {
            $status = strtr(get_pref('arc_twitter_message'), array(
                '{title}' => $title,
                '{url}'   => $url,
            ));

            // TODO: this is wrong. Doesn't support UNICODE.

            if ($over = min(0, strlen($status) - 140))
            {
                if ($iteartion === 1)
                {
                    $title = trim(substr($title, 0, min(strlen($title) - $over, 5))).'...';
                    continue;
                }

                return; // TODO: too long status message error
            }

            break;
        }

        $twitter = new Arc_Twitter_API(null, null);
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
     *
     * @param  string $event
     * @param  string $step
     * @param  string $default
     * @param  array  $rs
     * @return string
     */

    public function ui($event, $step, $default, $rs)
    {
        extract(gpsa(array(
            'arc_twitter_tweet',
            'arc_twitter_message',
        )));

        // TODO: pull and display used values on already shared articles.

        if (!isset($_POST['arc_twitter_message']))
        {
            $arc_twitter_message = get_pref('arc_twitter_message');
            $arc_twitter_tweet = get_pref('arc_twitter_tweet');
        }

        return $default . wrapRegion(
            'arc_twitter_share_article',

            graf(
                checkbox('arc_twitter_tweet', 1, (bool) $arc_twitter_tweet, 0, 'arc_twitter_tweet').
                tag(gTxt('arc_twitter_tweet_this'), 'label', array('for' => 'arc_twitter_tweet'))
            ).

            graf(
                tag(gTxt('arc_twitter_message'), 'label', array('for' => 'arc_twitter_message')).br.
                fInput('text', 'arc_twitter_message', $arc_twitter_message, '', '', '', INPUT_REGULAR, 0, 'arc_twitter_message')
            ),

            '',

            gTxt('arc_twitter')
        );
    }
}