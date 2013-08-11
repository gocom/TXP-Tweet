<?php

/**
 * Handles static Twitter button tags.
 */

class Arc_Twitter_Tag_Button
{
    /**
     * Renders a follow button.
     *
     * @param  array  $atts  Attributes
     * @param  string $thing Contained statement
     * @return string
     */

    static public function follow($atts, $thing = null)
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

    static public function share($atts, $thing = null)
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
}