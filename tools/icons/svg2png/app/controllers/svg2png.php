<?
class Svg2pngController extends Trails_Controller
{
    const EXTRAS_FILE = '../Vektor/16px/Vector-Zusaetze-16x16.svg';

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        $this->set_layout('layout');

        $this->inputs = array(
            1 => '../Vektor/16px/Vector-Iconset 16x16.svg',
            2 => '../Vektor/32px/Vector-Iconset 32x32.svg',
        );

        $this->extra_color = Request::get('extra-color', '#f00');
        if ($this->extra_color && $this->extra_color[0] != '#') {
            $this->extra_color = '#' . $this->extra_color;
        }

        if (Request::isPost()) {
            $this->input = $this->inputs[Request::int('input')];

            $this->size  = Request::int('size');
            $this->color = Request::get('color', false);
            if ($this->color && $this->color[0] != '#') {
                $this->color = '#' . $this->color;
            }

            $this->files = $this->convert($this->input, $this->size, $this->color);
        }
    }

    public function index_action()
    {
    }

    public function download_action()
    {
        $zip_name = sprintf('%1$s-%2$ux%2$u%3$s.zip',
                            reset(explode(' ', basename($this->input, '.svg'))),
                            $this->size,
                            $this->color ? '-' . $this->color : '');

        $zip = new ZipArchive();
        $zip->open($zip_name, ZipArchive::CREATE);

        $selected = Request::getArray('extras');
        if (!empty($selected)) {
            $color = Request::get('extra-color', '#f00');
            if ($color[0] != '#') {
                $color = '#' . $color;
            }

            $extras = $this->convert(self::EXTRAS_FILE, $this->size, $color);
            foreach ($extras as $file => $icon) {
                $extras[$file] = $this->border($icon);
            }
        }

        foreach ($this->files as $file => $png) {
            $zip->addFromString($file, $png);
            if (in_array($file, $selected)) {
                foreach ($extras as $prefix => $extra) {
                    $zip->addFromString($prefix . '/' . $file, $this->overlay($png, $extra));
                }
            }
        }

        $zip->close();

        $this->get_response()
              ->add_header('Content-Type', 'application/zip')
              ->add_header('Content-Disposition', 'attachment; filename="' . basename($zip_name) . '"')
              ->add_header('Expires', '0')
              ->add_header('Cache-Control', 'must-revalidate')
              ->add_header('Pragma', 'public')
              ->add_header('Content-Length', filesize($zip_name));
        $this->render_text(file_get_contents($zip_name));

        unlink($zip_name);
    }

    private function convert($svg, $size, $color)
    {
        $converter = SVG_Converter::CreateFrom($svg);
        $viewbox   = $converter->getViewBox();

        $icons = array();
        foreach ($converter->extractItems(true) as $id => $icon) {
            $file = sprintf('%s.png', $id ?: 'icon');

            $i = 1;
            while (isset($files[$file])) {
                $file = sprintf('%s-%u.png', $id ?: 'icon', $i++);
            }

            $icons[$file] = $icon;
        }

        $files = $converter->convertItems($icons, $size ?: $viewbox, $color);

        return $files;
    }

    private function overlay($image, $overlay)
    {
        $img = imagecreatefromstring($image);
        imagesavealpha($img, true);
        imagealphablending($img, true);

        $ovl = imagecreatefromstring($overlay);
        imagesavealpha($ovl, true);
        imagealphablending($ovl, true);

        for ($y = 0; $y < imagesy($img); $y++) {
            for ($x = 0; $x < imagesx($img); $x++) {
                $pixel = imagecolorat($ovl, $x, $y);
                $alpha = ($pixel & 0x7f000000) >> 24;
                if ($alpha != 127) {
                    imagesetpixel($img, $x, $y, $pixel);
                }
            }
        }

        ob_start();
        imagepng($img);
        $result = ob_get_clean();

        imagedestroy($img);
        imagedestroy($ovl);

        return $result;
    }

    private function border($img)
    {
        $image = imagecreatefromstring($img);
        $new   = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagesavealpha($new, true);
        imagealphablending($new, true);
        imagefill($new, 0, 0, IMG_COLOR_TRANSPARENT);
        $white = imagecolorallocate($new, 0xff, 0xff, 0xff);

        for ($y = 0; $y < imagesy($image); $y++) {
            for ($x = 0; $x < imagesx($image); $x++) {
                $pixel = imagecolorat($image, $x, $y);
                $alpha = ($pixel & 0x7f000000) >> 24;
                if ($alpha != 127) {
                    if ($x > 0) {
                        imagesetpixel($new, $x - 1, $y, $white);
                    }
                    if ($x < imagesx($image) - 1) {
                        imagesetpixel($new, $x + 1, $y, $white);
                    }
                    if ($y > 0 ) {
                        imagesetpixel($new, $x, $y - 1, $white);
                    }
                    if ($y < imagesy($image) - 1) {
                        imagesetpixel($new, $x, $y + 1, $white);
                    }
                }
            }
        }

        for ($y = 0; $y < imagesy($image); $y++) {
            for ($x = 0; $x < imagesx($image); $x++) {
                $pixel = imagecolorat($image, $x, $y);
                $alpha = ($pixel & 0x7f000000) >> 24;
                if ($alpha != 127) {
                    imagesetpixel($new, $x, $y, $pixel);
                }
            }
        }
        
        ob_start();
        imagepng($new);
        $result = ob_get_clean();
        
        return $result;
    }
}
