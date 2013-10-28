<?php

class OpenGraphURL extends SimpleORMap {

    static public $tempURLStorage = array();

    public function __construct($url = null) {
        $this->db_table = "opengraphdata";
        $this->registerCallback('before_store', 'serializeData');
        $this->registerCallback('after_store after_initialize', 'unserializeData');
        parent::__construct($url);
    }

    protected function serializeData() {
        $this->data = json_encode(studip_utf8encode((array) $this->data));
    }

    protected function unserializeData() {
        $this->data = (array) studip_utf8decode(json_decode($this->data));
    }

    public function fetch() {
        if (!get_config("OPENGRAPH_ENABLE")) {
            return;
        }
        $response = parse_link($this['url']);
        if ($response['response_code'] == 200) {
            $content = file_get_contents($this['url']);
            $currentEncoding = mb_detect_encoding($content);
            $currentEncoding || $currentEncoding = "UTF-8";
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

    public function render() {
        if (!get_config("OPENGRAPH_ENABLE") || !$this->getValue('is_opengraph')) {
            return "";
        }
        $template = $GLOBALS['template_factory']->open("shared/opengraphinfo_wide.php");
        $template->set_attribute('og', $this);
        return $template->render();
    }

    public function getAudioFiles() {
        $files = array();
        if ($_SERVER['HTTPS'] === 'on') {
            foreach ($this['data'] as $meta) {
                foreach ($meta as $key => $value) {
                    if ($key === "og:audio:secure_url") {
                        $files[] = array($value);
                    }
                    if ($key === "og:audio:type" && count($files)) {
                        $files[count($files) - 1][] = $value;
                    }
                }
            }
        }
        if (!count($files)) {
            foreach ($this['data'] as $meta) {
                foreach ($meta as $key => $value) {
                    if ($key === "og:audio") {
                        $files[] = array($value);
                    }
                    if ($key === "og:audio:type") {
                        $files[count($files) - 1][] = $value;
                    }
                }
            }
        }
        return $files;
    }

    public function getVideoFiles() {
        $files = array();
        if ($_SERVER['HTTPS'] === 'on') {
            foreach ($this['data'] as $meta) {
                foreach ($meta as $key => $value) {
                    if ($key === "og:video:secure_url") {
                        $files[] = array($value);
                    }
                    if ($key === "og:video:type" && count($files)) {
                        $files[count($files) - 1][] = $value;
                    }
                }
            }
        }
        if (!count($files)) {
            foreach ($this['data'] as $meta) {
                foreach ($meta as $key => $value) {
                    if ($key === "og:video") {
                        $files[] = array($value);
                    }
                    if ($key === "og:video:type") {
                        $files[count($files) - 1][] = $value;
                    }
                }
            }
        }
        return $files;
    }


}