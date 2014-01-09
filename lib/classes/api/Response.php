<?php
namespace API;

/**
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 * @since   Stud.IP 3.0
 */
class Response implements \ArrayAccess
{
    public $body, $status, $headers;

    public function __construct($body = '', $status = 200, $headers = array())
    {
        $this->body = $body;
        $this->status = (int) $status;
        $this->headers = (array) $headers;
    }

    // whether or not the status is set to 2xx
    public function isSuccess()
    {
        return 200 <= $this->status && $this->status <= 299;
    }

    public function finish($content_renderer)
    {
        if (!is_callable($this->body)) {
            $content_renderer->render($this);
        }
    }

    public function output()
    {
        if (isset($this->status)) {
            if (strpos(PHP_SAPI, 'cgi') === 0) {
                $this->sendHeader(sprintf('Status: %d %s', $this->status, $this->reason()));
            } else {
                $this->sendHeader(sprintf('HTTP/1.1 %d %s', $this->status, $this->reason()));
            }
        }

        foreach ($this->headers as $k => $v) {
            $this->sendHeader("$k: $v", false, $this->status);
        }

        if (is_callable($this->body)) {
            call_user_func($this->body);
        } else {
            echo $this->body;
        }
    }

    /**
     * Internally used function to actually send headers
     *
     * @param  string     the HTTP header
     * @param  bool       optional; TRUE if previously sent header should be
     *                    replaced - FALSE otherwise (default)
     * @param  integer    optional; the HTTP response code
     *
     * @return void
     */
    public function sendHeader($header, $replace = FALSE, $status = NULL) {
        if (isset($status)) {
            header($header, $replace, $status);
        }
        else {
            header($header, $replace);
        }
    }

    /**
     * Returns the reason phrase of this response according to RFC2616.
     *
     * @return string  the reason phrase for this response's status
     */
    public function reason() {
        $reason = array(
            100 => 'Continue', 'Switching Protocols',
            200 => 'OK', 'Created', 'Accepted', 'Non-Authoritative Information',
                   'No Content', 'Reset Content', 'Partial Content',
            300 => 'Multiple Choices', 'Moved Permanently', 'Found', 'See Other',
                   'Not Modified', 'Use Proxy', '(Unused)', 'Temporary Redirect',
            400 => 'Bad Request', 'Unauthorized', 'Payment Required','Forbidden',
                   'Not Found', 'Method Not Allowed', 'Not Acceptable',
                   'Proxy Authentication Required', 'Request Timeout', 'Conflict',
                   'Gone', 'Length Required', 'Precondition Failed',
                   'Request Entity Too Large', 'Request-URI Too Long',
                   'Unsupported Media Type', 'Requested Range Not Satisfiable',
                   'Expectation Failed',
            500 => 'Internal Server Error', 'Not Implemented', 'Bad Gateway',
                   'Service Unavailable', 'Gateway Timeout',
                   'HTTP Version Not Supported');

        return isset($reason[$this->status]) ? $reason[$this->status] : '';
    }

    // array access methods for headers
    public function offsetExists($offset)
    {
        return isset($this->headers[$offset]);
    }

    public function offsetGet($offset)
    {
        return @$this->headers[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->headers[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->headers[$offset]);
    }
}
