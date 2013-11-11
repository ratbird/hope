<?php
/*
 * Copyright (C) 2013 - Rasmus Fuhse <fuhse@data-quest.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

/**
 * A model class to handle the database table "opengraphdata", fetch data from
 * an Opengraph-URL and render a fitting box with the opengraph information to
 * the user.
 */
class OpenGraphURL extends SimpleORMap {

    static public $tempURLStorage = array(); //place to store opengraph-urls from a text.

    /**
     * constructor
     * @param string $url : the url that represents the opengraph-information
     */
    public function __construct($url = null) {
        $this->db_table = "opengraphdata";
        $this->registerCallback('before_store', 'serializeData');
        $this->registerCallback('after_store after_initialize', 'unserializeData');
        parent::__construct($url);
    }

    /**
     * Serialize the data field as a json-object before you store it in the database.
     */
    protected function serializeData() {
        $this->data = json_encode(studip_utf8encode((array) $this->data));
    }

    /**
     * Unserialize the data field when it comes from the database. So you can 
     * expect $url['data'] to be an array.
     */
    protected function unserializeData() {
        $this->data = (array) studip_utf8decode(json_decode($this->data));
    }


    /**
     * Fetches information from the url by getting the contents of the webpage, 
     * parse the webpage and extract the information from the opengraph meta-tags.
     * If the site doesn't have any opengraph-metatags it is in fact no opengraph
     * node and thus no data will be stored in the database. Only $url['is_opengraph'] === '0'
     * indicates that the site is no opengraph node at all.
     */
    public function fetch() {
        if (!get_config("OPENGRAPH_ENABLE")) {
            return;
        }
        $response = parse_link($this['url']);
        if ($response['response_code'] == 200 && strpos($response['Content-Type'],'html') !== false) {
            if (preg_match('/(?<=charset=)[^;]*/i', $response['Content-Type'], $match)) {
                $currentEncoding = $match[0];
            } else {
                $currentEncoding = "ISO-8859-1";
            }
            $content = file_get_contents($this['url']);
            $content = mb_encode_numericentity($content, array(0x80, 0xffff, 0, 0xffff), $currentEncoding);
            $old_libxml_error = libxml_use_internal_errors(true);
            $doc = new DOMDocument();
            $doc->loadHTML($content);
            libxml_use_internal_errors($old_libxml_error);

            $metatags = $doc->getElementsByTagName('meta');
            $reservedTags = array('url', 'chdate', 'mkdate', 'last_update', 'is_opengraph', 'data');
            $isOpenGraph = false;
            $ogTags = array();
            $data = array();
            foreach ($metatags as $tag) {
                $key = false;
                if ($tag->hasAttribute('property') &&
                        strpos($tag->getAttribute('property'), 'og:') === 0) {
                    $key = strtolower(substr($tag->getAttribute('property'), 3));
                }
                if (!$key &&
                        $tag->hasAttribute('name') &&
                        strpos($tag->getAttribute('name'), 'og:') === 0) {
                    $key = strtolower(substr($tag->getAttribute('name'), 3));
                }
                if ($key) {
                    $content = studip_utf8decode($tag->getAttribute('content'));
                    $data[] = array('og:'.$key => $content);
                    $ogTags[$key] = $content;
                    $isOpenGraph = true;
                }
            }
            foreach ($ogTags as $key => $tag) {
                if ($this->isField($key) && !in_array($key, $reservedTags)) {
                    $this[$key] = $tag;
                }
            }
            if (!$this['title'] && $isOpenGraph) {
                $titles = $doc->getElementsByTagName('title');
                if ($titles->length > 0) {
                    $this['title'] = studip_utf8decode($titles->item(0)->textContent);
                }
            }
            if (!$this['description'] && $isOpenGraph) {
                foreach ($metatags as $tag) {
                    if (stripos($tag->getAttribute('name'), "description") !== false
                            || stripos($tag->getAttribute('property'), "description") !== false) {
                        $this['description'] = studip_utf8decode($tag->getAttribute('content'));
                    }
                }
            }
            $this['data'] = $data;
        }
        $this['is_opengraph'] = (int) $isOpenGraph;
    }

    /**
     * Renders a small box with the information of the opengraph url. Used in 
     * blubber and in the forum.
     * @return string : html output of the box.
     */
    public function render() {
        if (!get_config("OPENGRAPH_ENABLE") || !$this->getValue('is_opengraph')) {
            return "";
        }
        $template = $GLOBALS['template_factory']->open("shared/opengraphinfo_wide.php");
        $template->set_attribute('og', $this);
        return $template->render();
    }

    public function getAudioFiles() {
        return $this->getMediaFiles("audio");
    }

    public function getVideoFiles() {
        return $this->getMediaFiles("video");
    }
    
    protected function getMediaFiles($type) {
        $files = array();
        $media = array();
        $secure_media = array();
        $media_types = array();
        foreach ($this['data'] as $meta) {
            foreach ($meta as $key => $value) {
                switch ($key) {
                    case "og:$type":
                        $media[] = $value;
                        break;
                    case "og:$type:secure_url":
                        $secure_media[] = $value;
                        break;
                    case "og:$type:type":
                        $media_types[] = $value;
                        break;
                }
            }
        }
        if ($_SERVER['HTTPS'] === 'on' && count($secure_media)) {
            foreach ($secure_media as $index => $url) {
                $files[] = array($url, $media_types[$index]);
            }
        } else {
            foreach ($media as $index => $url) {
                $files[] = array($url, $media_types[$index]);
            }
        }
        return $files;
    }


}