<?php

/**
 * Twitter API Wrapper.
 */

class Arc_Twitter_API extends TwitterOAuth
{
    // Caching variables
    private $_cache = true;
    private $_cache_dir = './tmp';
    private $_cache_time = 1800; // 30 minute cache

    function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL)
    {
        parent::__construct(
            get_pref('arc_twitter_consumer_key'),
            get_pref('arc_twitter_consumer_secret'),
            get_pref('arc_twitter_access_token'),
            get_pref('arc_twitter_access_token_secret')
        );

        $this->format = 'json';
        $this->timeout = 15;
        $this->connecttimeout = 15;
    }

    public function callbackURL($event,$step)
    {
        return preg_replace('/\?.*/', '',PROTOCOL.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'])
          .'?event='.$event.'&amp;step='.$step;
    }

    // create Twitter and external links in text
    public static function makeLinks($text)
    {
        $url = '/\b(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?([\/\w+\.]+)\b/i';
        $text = preg_replace($url, "<a href='$0' rel='external'>$0</a>", $text);
        $url = '/\b(^|\s)www.([a-z_A-Z0-9]+)((\.[a-z]+)+)\b/i';
        $text = preg_replace($url, "<a href='http://www.$2$3' rel='external'>www.$2$3</a>", $text);
        $text = preg_replace("/(^|\s).?@([a-z_A-Z0-9]+)/",
            "$1@<a href='http://twitter.com/$2' rel='external'>$2</a>",$text);
        $text = preg_replace("/(^|\s)(\#([a-z_A-Z0-9:_-]+))/",
            "$1<a href='http://twitter.com/search?q=%23$3' rel='external'>$2</a>",$text);
        return $text;
  }

    public function get($url, $params = array())
    {
        $api_url = md5($url.urlencode(serialize($params)));
        $data = '';

        if ($this->_cache) { // check for cached json

            $data = $this->_retrieveCache($api_url);

        }
        if (empty($data)) {
            $data = parent::get($url, $params); // data already json_decode'd
            if ($this->http_code===200 && $encoded_data=json_encode($data)) { // save cache
                $file = $this->_cache_dir.'/'.$api_url;
                file_put_contents($file,$encoded_data,LOCK_EX);
                return $data;
            } else { // failed to retrieve data from Twitter

                if ($this->_cache) { // attempt to force cached json return

                    $data = $this->_retrieveCache($api_url,true);
                    if ($data) return json_decode($data);

                }

                return false;

            }
        } else { // return cached json
            return json_decode($data);
        }
    } //end get()

    function post($url, $params = array())
    {
        $data = parent::post($url,$params);
        return $data;
    }

    function delete($url, $params = array())
    {
        $data = parent::delete($url,$params);
        return $data;
    }

    // Cache methods

    public function setCaching($cache=true)
    {
        $this->_cache = ($cache) ? true : false;
        return true;
    }

    public function cacheDir($loc)
    {
        $this->_cache_dir = $loc;
        return true;
    }

    public function cacheTime($mins)
    {
        $this->_cache_time = $mins*60; // convert minutes into seconds
        return true;
    }

    private function _retrieveCache($url,$overide_timeout=false)
    {
        $file = $this->_cache_dir.'/'.$url;
        if (file_exists($file)) {

            $diff = time() - filemtime($file);
            if ($overide_timeout || $diff < $this->_cache_time) {
                return file_get_contents($file);
            } else {
                return false;
            }

        } else {
            return false;
        }
    }
}