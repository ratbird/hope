<?php
namespace RESTAPI\Renderer;

/**
 * Content renderer for json content.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 * @since   Stud.IP 3.0
 */
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

        if (isset($response->body)) {
            $response->body = json_encode(self::utf8encodeRecursive($response->body));
        }
    }


    /**
     * This function tries to encode data of any type from Windows-1252 to
     * UTF-8, and returns the encoded version.
     *
     * If the argument `$data` is an array or an object that implements
     * `Traversable`, this function returns an associative array. Its keys
     * are encoded to UTF-8 and its values are send to this function
     * again.
     *
     * If the argument `$data` is a string or an object that responds to
     * `__toString`, this function casts it to a string and encodes it to
     * UTF-8.
     *
     * If the argument `$data` is of another scalar type (integer, float
     * or boolean) or is null, this function just returns that value
     * unchanged.
     *
     * If neither of these criteria match, this functions throws an
     * InvalidArgumentException.
     *
     * @param $data mixed  some data of any type that shall be encoded to
     *                     UTF-8 in the aforementioned manner
     *
     * @return mixed  that data encoded to UTF-8 as far as possible, see above
     *
     * @throws InvalidArgumentException This exception is thrown if there
     * is no way to encode such an object to UTF-8, e.g. database
     * connections, file handles etc.
     */
    private static function utf8encodeRecursive($data)
    {
        // array-artiges wird rekursiv durchlaufen
        if (is_array($data) || $data instanceof \Traversable) {
            $new_data = array();
            foreach ($data as $key => $value) {
                $key = studip_utf8encode((string) $key);
                $new_data[$key] = self::utf8encodeRecursive($value);
            }
            return $new_data;
        }

        // string-artiges wird an die nicht-rekursive Variante übergeben
        else if (is_string($data) || is_callable(array($data, '__toString'))) {
            return studip_utf8encode((string) $data);
        }

        // skalare Werte und `null` wird so durchgeschleift
        elseif (is_null($data) || is_scalar($data)) {
            return $data;
        }

        // alles andere ist ungültig
        throw new \InvalidArgumentException();
    }
}
