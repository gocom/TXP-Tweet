<?php

new Arc_Twitter_Install();
new Arc_Twitter_Admin();
new Arc_Twitter_Publish();

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
        'class'        => 'arc_twitter',
        'title'        => '',
    ), $atts));

    if ($article)
    {
        $tweet = (object) safe_row('*', 'arc_twitter', "article = '".doSlash($article)."'");
        return parse($thing);
    }

    if ($type !== null)
    {
        if ($type === 'created_at')
        {
            return safe_strftime($format, strtotime($tweet->created_at));
        }

        if ($type === 'status_url')
        {
            $url = 'https://twitter.com/'.urlencode($user).'/status/'.urlencode($tweet->status_id);

            if ($thing === null)
            {
                return $url;
            }

            return href(parse($thing), $url, array(
                'class' => $class,
                'title' => $title,
            ));
        }

        return txpspecialchars($tweet->{$type});
    }

    $twitter = new Arc_Twitter_API();

    switch ($timeline)
    {
        case 'home':
        case 'friends':
            $timeline = 'statuses/friends_timeline';
            break;
        case 'mentions':
            $timeline = 'statuses/mentions';
            break;
        default:
            $timeline = 'statuses/user_timeline';
    }

    $out = array();

    $tweets = $twitter->get($timeline, array(
        'screen_name'     => $user,
        'count'           => $limit,
        'include_rts'     => $retweets,
        'exclude_replies' => !$replies,
    ));

    if ($tweets)
    {
        foreach ($tweets as $tweet)
        {
            $out[] = parse($thing);
        }

        return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
    }

    return '';
}

function arc_twitter_search($atts)
{
    global $prefs;

    extract(lAtts(array(
        'user'      => $prefs['arc_twitter_user'],
        'search'    => '',
        'hashtags'  => '',
        'user'      => '',
        'reply'     => '',
        'mention'   => '',
        'limit'     => '10',
        'lang'      => '',
        'dateformat'=> $prefs['archive_dateformat'],
        'label'     => '',
        'labeltag'  => '',
        'break'     => 'li',
        'wraptag'   => '',
        'class'     => __FUNCTION__,
        'class_posted' => __FUNCTION__.'-posted',
        'class_user'   => __FUNCTION__.'-user'
    ),$atts));

    $twit = new Arc_Twitter_API();

        // construct search query
        if (!empty($search)) {
            $terms = explode(',',$search); $terms = array_map('trim',$terms);
            $search = implode(' ',$terms);
        }
        if ($hashtags) {
            $hashes = explode(',',$hashtags); $hashes = array_map('trim',$hashes);
            $search.= (($search)?' ':'').'#'.implode(' #',$hashes);
        }
        if ($reply) {
            $search.= (($search)?' ':'').'to:'.trim($reply);
        }
        if ($user) {
            $search.= (($search)?' ':'').'from:'.trim($user);
        }
        if ($mention) {
            $search.= (($search)?' ':'').'@'.trim($mention);
        }

        if (empty($search)) {
            return '';
        } else {
            $search = urlencode($search);
        }

        $out = array();
        $results = $twit->get('search/tweets'
            , array('q'=>$search,'count'=>$limit,'lang'=>$lang));

        $tweets = $results->statuses;
        if ($tweets) { foreach ($tweets as $tweet) {
            // preg_match("/(.*) \((.*)\)/",$tweet->user->screen_name,$matches);
            // list($author,$uname,$name) = $matches;
            $uname = $tweet->user->screen_name;
            $name = $tweet->user->name;
            $time = strtotime(htmlentities($tweet->created_at));
            $date = safe_strftime($dateformat,$time);
            $text = $tweet->text;
            $out[] = tag(href(htmlentities($uname),'http://twitter.com/' . $tweet->user->screen_name,
                ' title="'.htmlentities($name).'"').': ','span'
                    ,' class="'.$class_user.'"')
                .arc_Twitter::makeLinks(htmlentities($text, ENT_QUOTES,'UTF-8'))
                .' '.tag(htmlentities($date),'span'
                    ,' class="'.$class_posted.'"');
        } return doLabel($label, $labeltag)
            .doWrap($out, $wraptag, $break, $class); }

}

/**
 * Renders a Share button.
 *
 * @param  array  $atts  Attributes
 * @param  string $thing Contained statement
 * @return string
 */

function arc_twitter_share($atts, $thing = null)
{
    global $thisarticle;

    extract(lAtts(array(
        'via'     => get_pref('arc_twitter_user'),
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
 * Renders a Follow button.
 *
 * @param  array  $atts  Attributes
 * @param  string $thing Contained statement
 * @return string
 */

function arc_twitter_follow($atts, $thing = null)
{
    extract(lAtts(array(
        'user'   => get_pref('arc_twitter_user'),
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