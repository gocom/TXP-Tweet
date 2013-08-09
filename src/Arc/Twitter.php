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
  global $prefs,$arc_twitter_consumerKey, $arc_twitter_consumerSecret;

  extract(lAtts(array(
    'user'      => $prefs['arc_twitter_user'],
    'password'  => '',
    'timeline'  => 'user',
    'limit'     => '10',
    'fetch'     => 0,
    'retweets'  => false,
    'replies'   => true,
    'dateformat'=> $prefs['archive_dateformat'],
    'caching'   => '1',
    'cache_dir' => $prefs['arc_twitter_cache_dir'],
    'cache_time'=> '5',
    'label'     => '',
    'labeltag'  => '',
    'break'     => 'li',
    'wraptag'   => '',
    'class'     => __FUNCTION__,
    'class_posted' => __FUNCTION__.'-posted'
    ),$atts));

  $twit = new arc_twitter($arc_twitter_consumerKey
            , $arc_twitter_consumerSecret, $prefs['arc_twitter_accessToken']
            , $prefs['arc_twitter_accessTokenSecret']);

  if ($caching) {  // turn on caching, recommended (default)
    $twit->setCaching(true);
    $twit->cacheDir($cache_dir);
    $twit->cacheTime($cache_time);
  } else {  // turn off caching, not recommended other than for testing
    $twit->setCaching(false);
  }

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
    global $prefs,$arc_twitter_consumerKey, $arc_twitter_consumerSecret;

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
        'caching'   => '1',
        'cache_dir' => $prefs['arc_twitter_cache_dir'],
        'cache_time'=> '5',
        'label'     => '',
        'labeltag'  => '',
        'break'     => 'li',
        'wraptag'   => '',
        'class'     => __FUNCTION__,
        'class_posted' => __FUNCTION__.'-posted',
        'class_user'   => __FUNCTION__.'-user'
    ),$atts));

        $twit = new arc_twitter($arc_twitter_consumerKey
          , $arc_twitter_consumerSecret, $prefs['arc_twitter_accessToken']
          , $prefs['arc_twitter_accessTokenSecret']);

        if ($caching) {  // turn on caching, recommended (default)
            $twit->setCaching(true);
            $twit->cacheDir($cache_dir);
            $twit->cacheTime($cache_time);
        } else {  // turn off caching, not recommended other than for testing
            $twit->setCaching(false);
        }

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

function arc_twitter_tinyurl($atts, $thing=null) {
    global $thisarticle;

    extract(lAtts(array(
      'id'      => $thisarticle['thisid'],
      'title'   => '',
      'class'   => ''
    ),$atts));

    if ($id) {
      // Fetch arc_twitter stuff to build tweet from
      $tweet = safe_row("tinyurl"
        , 'arc_twitter', "article_id={$id}");
    }

    if ($tweet['tinyurl']) {
      if ($thing===null) {
        return $tweet['tinyurl'];
      }

      return href(parse($thing), $tweet['tinyurl'],
        ($title ? ' title="'.$title.'"' : '')
        .($class ? ' class="'.$class.'"' : ''));
    }
}

/*
 * Public tag for outputting widget JS
 */
function arc_twitter_widget_js($atts)
{
  extract(lAtts(array(
        'optimise' => false
    ),$atts));

  return _arc_twitter_widget_js($optimise);
}

function _arc_twitter_widget_js($optimise=true)
{
  global $arc_twitter;

  // Check if widget JS has already been output
  if ($arc_twitter['widget_js']) return;

  if ($optimise==false) {
    return '<script src="http://platform.twitter.com/widgets.js" type="text/javascript"></script>';
  }

  $js = <<<JS
<script type="text/javascript">
(function() {
  if (window.__twitterIntentHandler) return;
  var intentRegex = /twitter\.com(\:\d{2,4})?\/intent\/(\w+)/,
      windowOptions = 'scrollbars=yes,resizable=yes,toolbar=no,location=yes',
      width = 550,
      height = 420,
      winHeight = screen.height,
      winWidth = screen.width;

  function handleIntent(e) {
    e = e || window.event;
    var target = e.target || e.srcElement,
        m, left, top;

    while (target && target.nodeName.toLowerCase() !== 'a') {
      target = target.parentNode;
    }

    if (target && target.nodeName.toLowerCase() === 'a' && target.href) {
      m = target.href.match(intentRegex);
      if (m) {
        left = Math.round((winWidth / 2) - (width / 2));
        top = 0;

        if (winHeight > height) {
          top = Math.round((winHeight / 2) - (height / 2));
        }

        window.open(target.href, 'intent', windowOptions + ',width=' + width +
                                           ',height=' + height + ',left=' + left + ',top=' + top);
        e.returnValue = false;
        e.preventDefault && e.preventDefault();
      }
    }
  }

  if (document.addEventListener) {
    document.addEventListener('click', handleIntent, false);
  } else if (document.attachEvent) {
    document.attachEvent('onclick', handleIntent);
  }
  window.__twitterIntentHandler = true;
}());
</script>
JS;
  $arc_twitter['widget_js'] = true;
  return $js;
}

// Deprecated arc_twitter_retweet tag, use arc_twitter_tweet_button instead
function arc_twitter_retweet($atts, $thing=null)
{
  return arc_twitter_tweet_button($atts, $thing=null);
}
function arc_twitter_tweet_button($atts, $thing=null)
{
    global $prefs,$arc_twitter_consumerKey, $arc_twitter_consumerSecret;
    global $thisarticle;

    extract(lAtts(array(
        'user'        => $prefs['arc_twitter_user'], // via user account
        'url'         => '',
        'text'        => '',
        'follow1'     => '',
        'follow2'     => '',
        'lang'        => '',
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

    switch ($lang) {
      case 'fr': break; case 'de': break; case 'es': break; case 'jp': break;
      default:
        $lang = 'en';
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

/*
 * Twitter Follow button
 */
function arc_twitter_follow_button($atts, $thing=null)
{
    global $prefs,$arc_twitter_consumerKey, $arc_twitter_consumerSecret;
    global $thisarticle;

    extract(lAtts(array(
        'user'        => $prefs['arc_twitter_user'], // via user account
        'lang'        => '',
        'count'       => true,
        'include_js'  => true,
        'optimise_js' => false,
        'class'       => 'twitter-follow-button'
    ),$atts));

    $atts = ''; // data attributes

    switch ($lang) {
      case 'fr': break; case 'de': break; case 'es': break; case 'jp': break;
      default:
        $lang = 'en';
    }
    $atts .= ' data-lang="'.urlencode($lang).'"';

    $atts .= ' data-show-count="'.($count?'true':'false').'"';

    $thing = ($thing===null) ? 'Follow @'.$user : parse($thing);

    $html = href($thing,'http://twitter.com/'.urlencode($user)
      , ' class="'.$class.'"'.$atts);

    $js = ($include_js) ? _arc_twitter_widget_js($optimise_js?true:false) : '';

    return $js.$html;
}


function arc_twitter_url_method_select($name, $val)
{
    $methods = array('tinyurl' => 'Tinyurl',
      'isgd' => 'Is.gd',
      'smd' => 'smd_short_url');
    return selectInput($name, $methods, $val, '', '', $name);
}
function arc_twitter_tab_select($name, $val)
{
    $tabs = array('content' => 'Content',
        'extensions' => 'Extensions',
        '' => 'Hidden');
    return selectInput($name, $tabs, $val, '', '', $name);
}


function _arc_twitter_form_builder($fields) {

  $form = '';

  foreach ($fields as $fk => $fv) {

    $form .= ($fk) ? "<h2>$fk</h2>" : '';

    foreach ($fv as $k => $v) {

      $type = isset($v['type']) ? $v['type'] : 'text';

      $form .= "<p class='$k'>"
        ."<span class='edit-label'><label for='$k'>".$v['label']."</label></span>";

      switch ($type)  {

        case 'textarea':

          $form .= text_area($k, '50', '550', $v['value'], $k);
          break;

        case 'yesnoRadio':

          $form .= "<span class='edit-value'>".yesnoRadio($k, $v['value'], '', $k)."</span>";
          break;

        case 'arc_twitter_tab_select':

          $form .= "<span class='edit-value'>".arc_twitter_tab_select($k, $v['value'])."</span>";
          break;

        case 'arc_twitter_url_method_select':

          $form .= "<span class='edit-value'>".arc_twitter_url_method_select($k, $v['value'])."</span>";
          break;

        default:

          $form .= "<span class='edit-value'>".fInput('text',$k,$v['value'],'','','','','',$k)."</span>";
          break;

      }

      $form .= "</p>";
    }

  }

  return $form;
}

// Add Twitter tab to Textpattern
function arc_admin_twitter($event,$step)
{
    global $prefs, $arc_twitter_consumerKey, $arc_twitter_consumerSecret;

    $twit = new arc_twitter($arc_twitter_consumerKey
            , $arc_twitter_consumerSecret, $prefs['arc_twitter_accessToken']
            , $prefs['arc_twitter_accessTokenSecret']);

    $twit->cacheDir($prefs['arc_twitter_cache_dir']);

    $data = $twit->get('users/show'
        , array('screen_name'=>$prefs['arc_twitter_user']));
    $twitterUser = $data;
    $twitterUserURL = 'http://www.twitter.com/'.$twitterUser->screen_name;

    if ($step=="tweet") { // post an update to Twitter

        // fetch and clean message
        $message = strip_tags(gps('message'));
        $count = strlen($message);

        if ($count<=140 && $count>0) { // post update
            $result = $twit->post('statuses/update', array('status' => $message));
        } else { // message too long, JavaScript interface should prevent this
            $result = false;
        }

        pagetop('Twitter',
            (($result!=false)?'Twitter updated':'Error updating Twitter'));

    } elseif ($step=="delete") { // delete an update from Twitter

        $id = strip_tags(gps('id'));
        if ($id) {
            $twit->delete('statuses/destroy'.$id);
            safe_delete('arc_twitter',"tweet_id = $id");
        }

        pagetop('Twitter','Twitter updated');

    } else {

        pagetop('Twitter');

    }

    // Prepare JavaScript to create Twitter update interface

    $js = '<script language="javascript" type="text/javascript">';
    $js.= <<<JS
    $(document).ready(function(){
    var counter = $('<span>', {
        'text' : '140',
        'id' : 'tweetcount'
      });
    $('.message').append(counter);
            var counterStyle = 'font-weight:bold;padding-left:.5em;font-size:2em;line-height:1.2em;';
            $('#tweetcount').attr('style', counterStyle+'color:#ccc;');
            $('#message').keyup(function() {
                var count = 140-$('#message').val().length;
                $('#tweetcount').html(count+''); // hack to force output of 0
                if (count<0) {
                    $('input.publish').prop('disabled', 'disabled');
                } else {
                    $('input.publish').prop('disabled', '');
                }
                if (count<0) {
                    $('#tweetcount').attr('style', counterStyle+'color:#f00;');
                } else if (count<10) {
                    $('#tweetcount').attr('style', counterStyle+'color:#000;');
                } else {
                    $('#tweetcount').attr('style', counterStyle+'color:#ccc;');
                }
            })
        });
JS;
    $js.= "</script>";

    $out = '';
    $tweets = $twit->get('statuses/user_timeline'
        , array('screen_name'=>$prefs['arc_twitter_user'],'count'=>25));
    if ($tweets) foreach ($tweets as $tweet) {
        $time = strtotime(htmlentities($tweet->created_at));
        $date = safe_strftime($prefs['archive_dateformat'],$time);
        $out.= tr(td($date,'span')
            .td(arc_Twitter::makeLinks(htmlentities($tweet->text
                , ENT_QUOTES,'UTF-8')))
            .td(dLink('arc_admin_twitter','delete','id',$tweet->id,''))
            );
    }

    $fields = array(
    '' => array(
      'message' => array(
        'label' => 'Update Twitter',
        'type' => 'textarea',
        'value' => ''
      )
    )
    );

    $profile = '<img src="'.$twitterUser->profile_image_url.'" alt="Twitter avatar" style="float:left; margin-right: 1em" />'
    .graf(href($twitterUser->name,$twitterUserURL),' style="font-size:1.2em;font-weight:bold;"')
    .graf(href($twitterUser->friends_count.' following',$twitterUserURL.'/following')
    .', '.href($twitterUser->followers_count.' followers',$twitterUserURL.'/followers')
    .', '.href($twitterUser->statuses_count.' updates',$twitterUserURL));

    $form = _arc_twitter_form_builder($fields)
    .eInput('arc_admin_twitter')
    .sInput('tweet');
  $form .= '<p>'.fInput('submit', 'Submit', gTxt('Update'), 'publish').'</p>';

  $html = "<h1 class='txp-heading'>TXP Tweet</h1>"
    ."<p class='nav-tertiary'>"
    ."<a href='./?event=arc_admin_twitter' class='navlink-active'>Twitter</a><a href='./?event=plugin_prefs.arc_twitter' class='navlink'>Options</a>"
    ."</p>";

    $html .= "<div class='text-column'>".$profile."</div>"
    ."<br style='clear:both' />"
    .form("<div class='plugin-column'>".$form."</div>".br);

    // Attach recent Twitter updates

    $html.= "<div class='txp-listtables'>"
    .startTable('arc_twitter_timeline','','txp-list').$out.endTable()
    ."</div>";

    // Output JavaScript and HTML

    echo $js.$html;
}

/*
 * Shorten URLs using various methods
 */

function arc_shorten_url($url, $method='', $atts=array())
{
  global $prefs;

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);

  switch ($method) {
    case 'smd': // create a shortened URL using SMD Short URL
      return ($atts['id']) ? hu.$atts['id'] : false; break;
   

    case 'isgd':

      $u = 'http://is.gd/api.php?longurl='.urlencode($url);
      curl_setopt($ch, CURLOPT_URL, $u);

      $tinyurl = curl_exec($ch);
      if ($tinyurl!='Error' && $tinyurl!='') {
        return $tinyurl;
      } else {
        trigger_error('arc_twitter failed to get a is.gd URL for '
            .$url,E_USER_WARNING);
      }
      break;

    case 'tinyurl': default: // create a shortened URL using TinyURL

      $u = 'http://tinyurl.com/api-create.php?url='.urlencode($url);
      curl_setopt($ch, CURLOPT_URL, $u);
      $tinyurl = curl_exec($ch);
      if ($tinyurl!='Error' && $tinyurl!='') {
        return $tinyurl;
      } else {
        trigger_error('arc_twitter failed to get a TinyURL for '.$url,E_USER_WARNING);
      }

  }

  return false; // fail

}