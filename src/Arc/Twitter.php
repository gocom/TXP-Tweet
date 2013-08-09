<?php

new Arc_Twitter_Install();
new Arc_Twitter_Admin();
new Arc_Twitter_Publish();

/*
    Public-side functions
    ================================================================
*/

function arc_twitter($atts)
{
  global $prefs;

  extract(lAtts(array(
    'user'      => $prefs['arc_twitter_user'],
    'password'  => '',
    'timeline'  => 'user',
    'limit'     => '10',
    'fetch'     => 0,
    'retweets'  => false,
    'replies'   => true,
    'dateformat'=> $prefs['archive_dateformat'],
    'label'     => '',
    'labeltag'  => '',
    'break'     => 'li',
    'wraptag'   => '',
    'class'     => __FUNCTION__,
    'class_posted' => __FUNCTION__.'-posted'
    ),$atts));

    $twit = new Arc_Twitter_API();

  switch ($timeline) {
    case 'home': case 'friends':
      $timeline = 'statuses/friends_timeline'; break;
    case 'mentions':
      $timeline = 'statuses/mentions'; break;
    case 'user': default: $timeline = 'statuses/user_timeline';
  }

  // Check that the fetch (Twitter's count attribute) is set correctly
  $fetch = (!$fetch || $fetch<$limit) ? $limit : $fetch;

  $out = array();
  $tweets = $twit->get($timeline, array(
      'screen_name'=>$user,
      'count'=>$fetch,
      'include_rts'=>$retweets,
      'exclude_replies'=>!$replies
    ));
    
  if ($tweets) {
    // Apply the display limit to the returned tweets
    $tweets = array_slice($tweets, 0, $limit);
    foreach ($tweets as $tweet) {
      $time = strtotime(htmlentities($tweet->created_at));
      $date = safe_strftime($dateformat,$time);
      $out[] = arc_Twitter::makeLinks(htmlentities($tweet->text, ENT_QUOTES,'UTF-8'))
        .' '.tag(htmlentities($date),'span',' class="'.$class_posted.'"');
    }
  }

    return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);

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

function arc_twitter_tweet($atts) {
    global $thisarticle;

    extract(lAtts(array(
      'id'        => $thisarticle['thisid'],
      'include_url'   => true
    ),$atts));

    if ($id) {
      // Fetch arc_twitter stuff to build tweet from
      $tweet = ($include_url) ? safe_row("tweet", 'arc_twitter', "article_id={$id}")
        : safe_row("REPLACE(tweet,CONCAT(' ',tinyurl),'') AS tweet"
          , 'arc_twitter', "article_id={$id}");
    }

    if ($tweet['tweet']) {
      return arc_Twitter::makeLinks(
        htmlentities($tweet['tweet'], ENT_QUOTES,'UTF-8'));
    }
}

function arc_twitter_tweet_url($atts, $thing=null) {
    global $thisarticle,$prefs;

    extract(lAtts(array(
      'id'      => $thisarticle['thisid'],
      'title'   => '',
      'class'   => ''
    ),$atts));

    if ($id) {
      // Fetch arc_twitter stuff to build tweet from
      $tweet = safe_row("tweet_id"
        , 'arc_twitter', "article_id={$id}");
    }

    if ($tweet['tweet_id']) {
      $url = "http://twitter.com/".$prefs['arc_twitter_user']."/status/".$tweet['tweet_id'];
      if ($thing===null) {
        return $url;
      }
      return href(parse($thing), $url,
        ($title ? ' title="'.$title.'"' : '')
        .($class ? ' class="'.$class.'"' : ''));
    }
}

/*
 * Public tag for outputting widget JS
 */
function arc_twitter_widget_js()
{
    return _arc_twitter_widget_js();
}

function _arc_twitter_widget_js()
{
    return '<script src="http://platform.twitter.com/widgets.js" type="text/javascript"></script>';
}


function arc_twitter_tweet_button($atts, $thing=null)
{
    global $prefs, $thisarticle;

    extract(lAtts(array(
        'user'        => $prefs['arc_twitter_user'], // via user account
        'url'         => '',
        'text'        => '',
        'follow1'     => '',
        'follow2'     => '',
        'lang'        => 'en',
        'count'       => 'horizontal',
        'include_js'  => true,
        'optimise_js' => false,
        'wraptag'     => '',
        'class'       => 'twitter-share-button'
    ),$atts));

    $q = ''; // query string

    if ($id=$thisarticle['thisid']) {
      // Fetch arc_twitter stuff to build tweet from
      $row = safe_row("REPLACE(tweet,CONCAT(' ',tinyurl),'') AS tweet,tinyurl"
        , 'arc_twitter', "article_id={$id}");

      if ($url=='') {
        $url = ($url) ? $url : permlinkurl($thisarticle);
        $q = 'url='.urlencode($url);
      }
      if ($text=='') {
        $text = ($row['tweet']) ? $row['tweet'] : $thisarticle['title'];
      }
      $q .= ($q ? '&amp;' : '').'text='.urlencode($text);
    }
    if ($user) {
      $q .= ($q ? '&amp;' : '').'via='.urlencode($user);
    }
    if ($follow1&&$follow2) {
      $q .= ($q ? '&amp;' : '').'related='.urlencode($follow1.':'.$follow2);
    } elseif ($follow1||$follow2) {
      $q .= ($q ? '&amp;' : '').'related='.urlencode($follow1.$follow2);
    }

    
    $q .= ($q ? '&amp;' : '').'lang='.urlencode($lang);

    switch ($count) {
      case 'none': break; case 'vertical': break;
      default:
        $count = 'horizontal';
    }
    $q .= ($q ? '&amp;' : '').'count='.urlencode($count);

    $thing = ($thing===null) ? 'Tweet' : parse($thing);

    $html = href($thing,'http://twitter.com/share?'.$q
      , ' class="'.$class.'"');

    $js = ($include_js) ? _arc_twitter_widget_js($optimise_js?true:false) : '';

    return $js.$html;
}

/**
 * Renders a Follow button.
 *
 * @param  array  $atts  Attributes
 * @param  string $thing Contained statement
 * @return string
 */

function arc_twitter_follow_button($atts, $thing = null)
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

    return href(parse($thing), 'http://twitter.com/'.urlencode($user), array(
        'data-lang'       => $lang,
        'data-show-count' => $count,
        'class'           => $class,
    )) . '<script src="http://platform.twitter.com/widgets.js"></script>';
}