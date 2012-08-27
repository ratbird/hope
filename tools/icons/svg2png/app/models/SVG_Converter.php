<?
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

    public function convertItems($icons, $size = false, $color = false, $bgcolor = false)
    {
        $tmp_dir = '/tmp/' . md5(uniqid('svg', true));
        mkdir($tmp_dir) or die('Could not create temp directory');

        $files = array();
        foreach ($icons as $file => $icon) {
            $icon = str_replace(' display="none"', '', $icon);
            if ($color && strpos($icon, 'fill=') === false) {
                $icon = preg_replace('/<(circle|path|polygon|rect) /', '<$1 fill="' . $color . '" ', $icon);
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
                           $size ?: $this->getViewBox(),
                           $icon,
                           $this->getViewBox());

            $tmp_file = $tmp_dir . '/' . md5(uniqid('svg-file', true)) . '.svg';
            file_put_contents($tmp_file, $svg);
            $files[$file] = $tmp_file;
        }

        if ($bgcolor) {
            $rgb = array_map('hexdec', str_split(substr($bgcolor, 1), 2));
            $bgcolor = sprintf('-bg 1.%s.%s.%s', $rgb[0], $rgb[1], $rgb[2]);
        }

        $command = sprintf('java -jar vendor/batik/batik-rasterizer.jar %3$s -w %1$u -h %1$u -cssMedia image/png -d %2$s %2$s/*.svg', $size, $tmp_dir, $bgcolor ?: '');
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
