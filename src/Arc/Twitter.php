<?php

class Arc_Twitter
{
    /**
     * The current row.
     *
     * @var array
     */

    protected $current = array();

    /**
     * Constructor.
     */

    public function __construct()
    {
        new Arc_Twitter_Install();
        new Arc_Twitter_Admin();
        new Arc_Twitter_Admin_Article();
        new Arc_Twitter_Auth();

        Textpattern_Tag_Registry::register(array($this, 'follow'), 'arc_twitter_follow');
        Textpattern_Tag_Registry::register(array($this, 'share'), 'arc_twitter_share');
    }

    /**
     * Renders a follow button.
     *
     * @param  array  $atts  Attributes
     * @param  string $thing Contained statement
     * @return string
     */

    public function follow($atts, $thing = null)
    {
        extract(lAtts(array(
            'user'   => '',
            'lang'   => 'en',
            'count'  => 'true',
            'class'  => 'twitter-follow-button',
        ), $atts));

        if ($thing === null)
        {
            $thing = 'Follow @'.txpspecialchars($user);
        }

        return href(parse($thing), 'https://twitter.com/'.urlencode($user), array(
            'data-lang'       => $lang,
            'data-show-count' => $count,
            'class'           => $class,
        )) . '<script src="//platform.twitter.com/widgets.js"></script>';
    }

    /**
     * Renders a share button.
     *
     * @param  array  $atts  Attributes
     * @param  string $thing Contained statement
     * @return string
     */

    public function share($atts, $thing = null)
    {
        global $thisarticle;

        extract(lAtts(array(
            'via'     => '',
            'url'     => null,
            'text'    => null,
            'related' => '',
            'lang'    => 'en',
            'count'   => 'horizontal',
            'class'   => 'twitter-share-button',
        ), $atts));

        $qs = $atts;
        $qs['related'] = join(':', do_list($related));
        unset($qs['class']);

        if (!empty($thisarticle['thisid']))
        {
            if ($url === null)
            {
                $qs['url'] = permlinkurl($thisarticle);
            }

            if ($text === null)
            {
                $qs['text'] = $thisarticle['title'];
            }
        }

        return href(parse($thing), 'https://twitter.com/share' . join_qs($qs), array(
            'class' => $class,
        )) . '<script src="//platform.twitter.com/widgets.js"></script>';
    }

    /**
     * Gets a value from multi-dimensional array based on the given query.
     *
     * @param  string $name The query string
     * @return mixed
     */

    public function getValue($name)
    {
        $value = $this->current;

        foreach (do_list($name, '->') as $property)
        {
            if (!array_key_exists($property, $value))
            {
                return '';
            }

            $value = $value[$property];
        }

        return $value;
    }
}

new Arc_Twitter();

/**
 * Renders Twitter timeline.
 *
 * @param  array  $atts  Attributes
 * @param  string $thing Contained statement
 * @return string
 */

function arc_twitter($atts, $thing = null)
{
    static $tweet = array();

    extract(lAtts(array(
        'article'      => null,
        'type'         => null,
        'format'       => 'since',
        'user'         => get_pref('arc_twitter_user'),
        'timeline'     => 'user',
        'limit'        => 10,
        'retweets'     => 0,
        'replies'      => 1,
        'label'        => '',
        'labeltag'     => '',
        'break'        => '',
        'wraptag'      => '',
        'class'        => '',
        'title'        => '',
        'search'       => '',
        'mention'      => '',
        'reply'        => '',
        'hashtags'     => '',
        'status'       => '',
    ), $atts));

    if ($article)
    {
        // TODO: support comma-separated list of IDs.
        $tweet = safe_row('*', 'arc_twitter', "article = '".doSlash($article)."'");
        return parse($thing);
    }

    if ($type !== null)
    {
        if ($type === 'created_at')
        {
            return safe_strftime($format, strtotime($tweet['created_at']));
        }

        if ($type === 'status_url')
        {
            $url = 'https://twitter.com/'.urlencode($user).'/status/'.urlencode($tweet['status_id']);

            if ($thing === null)
            {
                return $url;
            }

            return href(parse($thing), $url, array(
                'class' => $class,
                'title' => $title,
            ));
        }

        return txpspecialchars($tweet[$type]); // TODO: support nested keys.
    }

    $twitter = new Arc_Twitter_API(null, null);

    if ($timeline === 'home')
    {
        $tweets = $twitter->statusesHomeTimeline($limit, null, null, null, !$replies, null, null);
    }
    else if ($timeline === 'mentions')
    {
        $tweets = $twitter->statusesMentionsTimeline($limit);
    }
    else if ($timeline === 'retweets')
    {
        $tweets = $twitter->statusesRetweets($status);
    }
    else if ($timeline === 'show')
    {
        $tweets = $twitter->statusesShow($status);
    }
    else if ($timeline === 'favorites')
    {
        $tweets = $twitter->favoritesList(null, $user, $limit);
    }
    else if ($timeline === 'search')
    {
        $q = do_list($search);

        if ($user)
        {
            $q[] = 'from:' . implode(' from:', do_list($user));
        }

        if ($reply)
        {
            $q[] = 'to:' . implode(' to:', do_list($reply));
        }

        if ($mention)
        {
            $q[] = '@' . implode(' @', do_list($mention));
        }

        if ($hashtag)
        {
            $q[] = '#' . implode(' #', do_list($hashtag));
        }

        $q = urlencode(trim(implode(' ', $q)));
        $tweets = $twitter->searchTweets($q, null, null, null, null, $limit, null, null, null, null);
    }
    else
    {
        $tweets = $twitter->statusesUserTimeline(null, $user, null, $limit, null, null, !$replies, null, $retweets);
    }

    if ($tweets)
    {
        $out = array();

        foreach ($tweets as $tweet)
        {
            $out[] = parse($thing);
        }

        return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
    }

    return '';
}