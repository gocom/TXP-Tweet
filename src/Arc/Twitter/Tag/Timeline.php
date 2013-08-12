<?php

class Arc_Twitter_Tag_Timeline
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
        Textpattern_Tag_Registry::register(array($this, 'timeline'), 'arc_twitter');
        Textpattern_Tag_Registry::register(array($this, 'value'), 'arc_twitter_value');
    }

    /**
     * Renders Twitter timeline.
     *
     * @param  array  $atts  Attributes
     * @param  string $thing Contained statement
     * @return string
     */

    public function timeline($atts, $thing = null)
    {
        extract(lAtts(array(
            'article'      => null,
            'user'         => '',
            'timeline'     => 'user',
            'limit'        => 10, // TODO: Twitter filters tweets after fetching, calculate count.
            'retweets'     => 0,
            'replies'      => 1,
            'label'        => '',
            'labeltag'     => '',
            'break'        => '',
            'wraptag'      => '',
            'class'        => '',
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
                $this->current = $tweet; // TODO: support nesting.
                $out[] = parse($thing);
            }

            return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
        }

        return '';
    }

    /**
     * Renders the item value.
     */

    public function value($atts, $thing = null)
    {
        extract(lAtts(array(
            'name' => '',
        ), $atts));

        $value = $this->current;
        $method = 'format';

        foreach (do_list($name, '->') as $property)
        {
            if (!array_key_exists($property, $value))
            {
                return '';
            }

            $method .= implode('', doArray(do_list($property, '_'), 'ucfirst'));
            $value = $value[$property];
        }

        if (method_exists($this, $method))
        {
            return $this->$method($atts, $thing, $value);
        }

        return txpspecialchars($value);
    }

    /**
     * Formats created at.
     */

    protected function formatCreatedAt($atts, $thing, $value)
    {
        extract(lAtts(array(
            'format' => 'since'
        ), $atts));

        return safe_strftime($format, (int) strtotime($value));
    }

    /**
     * Formats status URL.
     */

    protected function formatStatusUrl($atts, $thing, $value)
    {
        extract(lAtts(array(
            'class' => '',
            'title' => '',
        ), $atts));

        $url = 'https://twitter.com/'.urlencode($this->current['user']['screen_name']).'/status/'.urlencode($this->current['id_str']);

        if ($thing === null)
        {
            return $url;
        }

        return href(parse($thing), $url, array(
            'class' => $class,
            'title' => $title,
        ));
    }
}