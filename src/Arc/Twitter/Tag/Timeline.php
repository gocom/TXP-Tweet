<?php

/**
 * Collection of Twitter timeline template tags.
 */

class Arc_Twitter_Tag_Timeline
{
    /**
     * The current row.
     *
     * @var array
     */

    protected $current = array();

    /**
     * Twitter API instance.
     *
     * @var Arc_Twitter_API
     */

    protected $api;

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
            'timeline' => 'user',
            'limit'    => 10,
            'label'    => '',
            'labeltag' => '',
            'break'    => '',
            'wraptag'  => '',
            'class'    => '',
        ), $atts, false));

        unset(
            $atts['timeline'],
            $atts['limit'],
            $atts['label'],
            $atts['labeltag'],
            $atts['break'],
            $atts['wraptag'],
            $atts['class']
        );

        if (!$this->api)
        {
            try
            {
                $this->api = new Arc_Twitter_API(null, null);
            }
            catch (Exception $e)
            {
                trigger_error($e->getMessage());
                return '';
            }
        }

        $method = 'timeline'.ucfirst($timeline);

        if (!method_exists($this, $method))
        {
            return '';
        }

        try
        {
            $tweets = $this->$method($atts);
        }
        catch (Exception $e)
        {
            trigger_error($e->getMessage());
            return '';
        }

        if ($tweets)
        {
            $out = array();
            $tweets = array_slice($tweets, 0, $limit);

            foreach ($tweets as $tweet)
            {
                $parent = $this->current;
                $this->current = $tweet;
                $out[] = parse($thing);
                $this->current = $parent;
            }

            return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
        }

        return '';
    }

    /**
     * Home timeline.
     *
     * @param  array $atts Attributes
     * @return array
     */

    protected function timelineHome($atts)
    {
        extract(lAtts(array(
            'limit'   => 10,
            'replies' => 1,
        ), $atts));

        return $this->api->statusesHomeTimeline(max(200, min(20, $limit)), null, null, null, !$replies, null, null);
    }

    /**
     * User timeline.
     *
     * @param  array $atts Attributes
     * @return array
     */

    protected function timelineUser($atts)
    {
        extract(lAtts(array(
            'user'     => get_pref('arc_twitter_account'),
            'limit'    => 10,
            'replies'  => 1,
            'retweets' => 1,
        ), $atts));

        return $this->api->statusesUserTimeline(null, $user, null, max(200, min(20, $limit)), null, null, !$replies, null, $retweets);
    }

    /**
     * Mentions timeline.
     *
     * @param  array $atts Attributes
     * @return array
     */

    protected function timelineMentions($atts)
    {
        extract(lAtts(array(
            'limit'   => 10,
        ), $atts));

        return $this->api->statusesMentionsTimeline($limit)
    }

    /**
     * Retweets timeline.
     *
     * @param  array $atts Attributes
     * @return array
     */

    protected function timelineRetweets($atts)
    {
        extract(lAtts(array(
            'status' => '',
        ), $atts));

        return $this->api->statusesRetweets($status);
    }

    /**
     * Shows an individual status.
     *
     * @param  array $atts Attributes
     * @return array
     */

    protected function timelineStatus($atts)
    {
        extract(lAtts(array(
            'status'  => '',
            'article' => null,
        ), $atts));

        if ($article !== null && !($status = safe_field('status_id', 'arc_twitter', 'article = '.intval($article))))
        {
            throw new Exception('Article '.intval($article).' does not have Twitter status.');
        }

        return $this->api->statusesShow($status);
    }

    /**
     * Favorites timeline.
     *
     * @param  array $atts Attributes
     * @return array
     */

    protected function timelineFavorites()
    {
        extract(lAtts(array(
            'user'  => get_pref('arc_twitter_account'),
            'limit' => 10,
        ), $atts));

        return $this->api->favoritesList(null, $user, $limit);
    }

    /**
     * Search timeline.
     *
     * @param  array $atts Attributes
     * @return array
     */

    protected function timelineSearch($atts)
    {
        extract(lAtts(array(
            'q'     => '',
            'limit' => 10,
        ), $atts));

        $q = urlencode(trim($q));
        return $this->api->searchTweets($q, null, null, null, null, $limit, null, null, null, null);
    }

    /**
     * Renders the item value.
     *
     * @param  array  $atts  Attributes
     * @param  string $thing Contained statement
     * @return string HTML markup
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
            return (string) $this->$method($atts, $thing, $value);
        }

        return txpspecialchars($value);
    }

    /**
     * Formats created at timestamp.
     *
     * @param  array  $atts  Attributes
     * @param  string $thing Contained statement
     * @param  string $value The value to format
     * @return string
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
     *
     * @param  array  $atts  Attributes
     * @param  string $thing Contained statement
     * @param  string $value The value to format
     * @return string
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