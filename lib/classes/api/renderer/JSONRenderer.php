<?php
/**
 * Content renderer for json content.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 */

namespace API\Renderer;

class JSONRenderer extends DefaultRenderer
{
    public function contentType()
    {
        return 'application/json';
    }

    public function extension()
    {
        return '.json';
    }

    public function render($response)
    {
        if (!isset($response['Content-Type'])) {
            $response['Content-Type'] = $this->contentType() . ';charset=utf-8';
        }

        if ($response->body) {
            $response->body = json_encode(studip_utf8encode($response->body));
        }
    }
}
