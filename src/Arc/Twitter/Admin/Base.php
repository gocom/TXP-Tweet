<?php

/**
 * Share content items on Twitter.
 */

class Arc_Twitter_Admin_Base implements Arc_Twitter_Admin_Template
{
    /**
     * Inserted data.
     *
     * @var array
     */

    protected $insertData = '';

    /**
     * {@inheritdoc}
     */

    public function tweet($event, $step, $r)
    {
        extract(psa(array(
            'arc_twitter_prefix',
            'arc_twitter_suffix',
            'arc_twitter_tweet',
        )));

        if (!$arc_twitter_tweet)
        {
            return;
        }

        $this->insertData = $r;

        if (!($title = $this->getTitle()) || !($url = $this->getURL()))
        {
            return;
        }

        // TODO: requires an adapter.

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
            // TODO: requires an adapter.

            safe_insert(
                'arc_twitter',
                "article = ".intval($r['ID']).",
                status_id = '".doSlash($result['id'])."',
                url = '".doSlash($url)."'"
            );
        }
    }

    /**
     * {@inheritdoc}
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
            'arc_twitter_share',

            graf(
                checkbox('arc_twitter_tweet', 1, (bool) $arc_twitter_tweet, 0, 'arc_twitter_tweet').
                tag(gTxt('arc_twitter_tweet_this'), 'label', array('for' => 'arc_twitter_tweet'))
            ).

            graf(
                tag(gTxt('arc_twitter_message'), 'label', array('for' => 'arc_twitter_message')).br.
                fInput('text', 'arc_twitter_message', $arc_twitter_message, '', '', '', INPUT_REGULAR, 0, 'arc_twitter_message')
            ),

            '',

            gTxt('arc_twitter_share')
        );
    }
}