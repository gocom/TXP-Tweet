<?php

/**
 * Interface for admin-side Twitter sharing widgets.
 */

interface Arc_Twitter_Admin_Template
{
    /**
     * Constructor.
     */

    public function __construct();

    /**
     * Publishes the article on Twitter.
     *
     * @todo Shorten the URL
     * @todo Catch Exceptions thrown by Twitter API
     */

    public function tweet($event, $step, $r);

    /**
     * Collapsing Twitter sharing options widget.
     *
     * @param  string $event   The event
     * @param  string $step    The step
     * @param  string $default Default markup
     * @param  array  $rs      Data
     * @return string HTML
     */

    public function ui($event, $step, $default, $rs);

    /**
     * Gets the item title for the status message.
     *
     * @return string
     */

    public function getTitle();

    /**
     * Gets the item URL for the status message.
     *
     * @return string
     */

    public function getURL();

    /**
     * Gets the item type.
     *
     * @return string
     */

    public function getType();

    /**
     * Gets the item ID.
     *
     * @return int
     */

    public function getID();

    /**
     * Gets the shared item.
     *
     * @return array|bool
     */

    public function shared();
}