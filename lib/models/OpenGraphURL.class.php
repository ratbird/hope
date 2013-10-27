<?php

class OpenGraphURL extends SimpleORMap {
    
    static private $desiredEncoding = "Windows-1252";
    static public $tempURLStorage = array();
    
    static public function setEncoding($encoding) {
        self::$desiredEncoding = $encoding;
    }
    
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
    
    public function fetch($desiredEncoding = null) {
        if (!get_config("OPENGRAPH_ENABLE")) {
            return;
        }
        $desiredEncoding || $desiredEncoding = self::$desiredEncoding;
        $content = file_get_contents($this['url']);
        if ($content) {
            $currentEncoding = mb_detect_encoding($content);
            $currentEncoding || $currentEncoding = "UTF-8";
            
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
                    $content = mb_convert_encoding($tag->getAttribute('content'), $desiredEncoding, $currentEncoding);
                    $data[] = array('og:'.$key => $content);
                    $ogTags[$key] = $content;
                    $isOpenGraph = true;
                }
                if ($tag->hasAttribute('charset')) {
                    $currentEncoding = $tag->getAttribute('charset');
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
                    $this['title'] = mb_convert_encoding($titles->item(0)->textContent, $desiredEncoding, $currentEncoding);
                }
            }
            if (!$this['description'] && $isOpenGraph) {
                foreach ($metatags as $tag) {
                    if (stripos($tag->getAttribute('name'), "description") !== false 
                            || stripos($tag->getAttribute('property'), "description") !== false) {
                        $this['description'] = mb_convert_encoding($tag->getAttribute('content'), $desiredEncoding, $currentEncoding);
                    }
                }
            }
            $this['data'] = $data;
        }
        $this['is_opengraph'] = (int) $isOpenGraph;
    }
    
    public function render() {
        if (!get_config("OPENGRAPH_ENABLE")) {
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