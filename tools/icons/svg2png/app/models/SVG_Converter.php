<?
/**
 * @author Jan-Hendrik Willms <tleilax+studip@gmail.com>
 */

// +---------------------------------------------------------------------------+
// Copyright (C) 2012 Jan-Hendrik Willms <tleilax+studip@gmail.com>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

class SVG_Converter
{
    private $filename;
    private $xml;

    private static $output_directory = '.';

    public static function setOutputDirectory($dir)
    {
        self::$output_directory = rtrim($dir, '/') . '/';
    }

    public static function CreateFrom($svg_file)
    {
        return new self($svg_file);
    }

    public function __construct($svg_file)
    {
        $this->filename = $svg_file;
        $this->xml      = simplexml_load_file($svg_file);
    }

    public function getViewBox()
    {
        static $viewbox;
        if (!$viewbox) {
            $attributes = (array)$this->xml->attributes();
            $viewbox = max(explode(' ', $attributes['@attributes']['viewBox']));
        }
        return $viewbox;
    }

    public function extractItems($encode_umlauts = true)
    {
        $result = array();

        foreach ($this->xml->g as $g) {
            foreach ($g as $svg) {
                $id = strtolower($svg['id'] ?: 'item');
                if ($encode_umlauts) {
                    $id = str_replace(array('ä', 'ö', 'ü', 'ß'), array('ae', 'oe', 'ue', 'ss'), $id);
                }

                if (isset($result[$id])) {
                    $i = 1;
                    do {
                        $temp = sprintf('%s-%u', $id, $i++);
                    } while (isset($result[$temp]));
                    $id = $temp;
                }

                $result[$id] = $svg->asXML();
            }
        }

        return $result;
    }

    public function convertItems($icons, $size = false, $color = false)
    {
        $tmp_dir = '/tmp/' . md5(uniqid('svg', true));
        mkdir($tmp_dir) or die('Could not create temp directory');

        $files = array();
        foreach ($icons as $file => $icon) {
            $icon = str_replace(' display="none"', '', $icon);
            if ($color && strpos($icon, 'fill=') === false) {
                $icon = preg_replace('/<(circle|path|polygon|rect|ellipse) /', '<$1 fill="' . $color . '" ', $icon);
            } else if ($color) {
                $icon = preg_replace('/fill="#([0-9a-f])(\\1{2,5})"/i', 'fill="##$1$2"', $icon);
                $icon = preg_replace('/fill="#[0-9a-f]{3,6}"/i', 'fill="' . $color . '"', $icon);
                $icon = preg_replace('/fill="##([0-9a-f]{3,6})"/i', 'fill="#$1"', $icon);
            }

            $svg = sprintf('<?xml version="1.0" encoding="utf-8"?>'
                          .'<svg version="1.1" xmlns="http://www.w3.org/2000/svg"'
                          .' xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve"'
                          .' x="0px" y="0px"'
                          .' width="%1$upx" height="%1$upx"'
                          .' viewBox="0 0 %3$u %3$u"'
#                          .' enable-background="new 0 0 %3$u %3$u"'
                          .'><g scale="100">%2$s</g></svg>',
                           $size,
                           $icon,
                           $this->getViewBox() ?: $size);

            $tmp_file = $tmp_dir . '/' . md5(uniqid('svg-file', true)) . '.svg';
            file_put_contents($tmp_file, $svg);
            $files[$file] = $tmp_file;
        }

        $command = sprintf('java -jar vendor/batik/batik-rasterizer.jar -w %1$u -h %1$u -cssMedia image/png -d %2$s %2$s/*.svg', $size, $tmp_dir);
        exec($command);

        $result = array();
        foreach ($files as $file => $temp) {
            $result[$file] = file_get_contents(preg_replace('/\.svg$/', '.png', $temp));
        }

        array_map('unlink', glob($tmp_dir . '/*'));
        rmdir($tmp_dir);

        return $result;

    }
}
