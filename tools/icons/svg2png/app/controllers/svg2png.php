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

class Svg2pngController extends Trails_Controller
{
    const EXTRAS_FILE = '../Vektor/16px/Vector-Zusaetze-16x16.svg';

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        $this->set_layout('layout');

        $this->inputs = array(
            1 => '../Vektor/16px/Vector-Iconset 16x16.svg',
            2 => '../Vektor/16px/Vector-Pfeile 16x16.svg',
            3 => '../Vektor/32px/Vector-Iconset 32x32.svg',
        );

        $this->extra_color = Request::get('extra-color', '#cb1800');
        if ($this->extra_color && $this->extra_color[0] != '#') {
            $this->extra_color = '#' . $this->extra_color;
        }
        $this->extra_offsets = Request::intArray('extra-offset') ?: array('x' => 0, 'y' => 0);
        $this->extra_distance = Request::int('extra-distance') ?: 1;

        $this->size  = Request::int('size') ?: 16;
        $this->border = Request::get('border') ?: '';
        $this->suffix = Request::get('suffix', '');
        $this->color = Request::getArray('color') ?: array('name' => array('black'), 'color' => array('#000000'));
        foreach ($this->color['color'] as $index => $color) {
            if ($color[0] != '#') {
                $this->color['color'][$index] = '#' . $color;
            }
        }
    }

    public function index_action()
    {
        if (Request::submitted('display')) {
            $this->input = $this->inputs[Request::int('input')];
            $this->files = $this->convert($this->input, $this->size, $this->border);
        }

        if (Request::submitted('add-color')) {
            $new_color = Request::get('new-color', '');
            if (!empty($new_color)) {
                list($label, $color) = explode('-', $new_color);
                $this->color['color'][] = $color;
                $this->color['name'][]  = $label;
            } else {
                $this->color['color'][] = '';
                $this->color['name'][]  = '';
            }
        }
    }

    public function download_action()
    {
        ini_set('max_execution_time', 0);
        
        $input = $this->inputs[Request::int('input')];

        $zip_name = sprintf('%1$s-%2$ux%2$u%3$s.zip',
                            reset(explode(' ', basename($input, '.svg'))),
                            $this->size,
                            count($this->color['color']) === 1 ? '-' . $this->color['color'][0] : '');
        $tmp_zip = '/tmp/' . md5($zip_name . uniqid('zip', true));

        $zip = new ZipArchive();
        $zip->open($tmp_zip, ZipArchive::CREATE);

        $selected = Request::getArray('extras');
        if (!empty($selected)) {
            $color = Request::get('extra-color', '#cb1800');
            if ($color[0] != '#') {
                $color = '#' . $color;
            }

            $extras = $this->convert(self::EXTRAS_FILE, $this->size, $this->border, $color, $this->extra_offsets);
            foreach ($extras as $file => $icon) {
                unset($extras[$file]);
                $file = reset(explode('.', $file));
                $extras[$file] = array(
                    'icon'  => $icon,
                    'punch' => $this->border($icon, $this->extra_distance),
                );
            }
        }

        foreach ($this->color['color'] as $index => $color) {
            $files = $this->convert($input, $this->size, $this->border, $color);
            $directory = $this->size . '/' . $this->color['name'][$index] . '/';

            foreach ($files as $file => $png) {
                $zip->addFromString($directory . $file, $png);
                if (in_array($file, $selected)) {
                    foreach ($extras as $prefix => $extra) {
                        $zip->addFromString($directory . $prefix . '/' . $file, $this->combine($png, $extra['icon'], $extra['punch']));
                    }
                }
            }
        }

        $zip->close();

        $this->get_response()
              ->add_header('Content-Type', 'application/zip')
              ->add_header('Content-Disposition', 'attachment; filename="' . $zip_name . '"')
              ->add_header('Expires', '0')
              ->add_header('Cache-Control', 'must-revalidate')
              ->add_header('Pragma', 'public')
              ->add_header('Content-Length', filesize($tmp_zip));
        $this->render_text(file_get_contents($tmp_zip));

        unlink($tmp_zip);
    }

    private function convert($svg, $size, $border = 0, $color = '#000000', $offsets = array())
    {
        $converter = SVG_Converter::CreateFrom($svg);
//        $viewbox   = $converter->getViewBox();

        $icons = array();
        foreach ($converter->extractItems(true) as $id => $icon) {
            $id = str_ireplace('_x5f_', '_', $id);
            $file = sprintf('%s%s.png', $id ?: 'icon', $this->suffix ?: '');

            $i = 1;
            while (isset($files[$file])) {
                $file = sprintf('%s-%u.png', $id ?: 'icon', $i++);
            }

            $icons[$file] = $icon;
        }

        $files = $converter->convertItems($icons, $size, $color, $border, $offsets);

        return $files;
    }

    private function combine($image, $overlay, $punch = false)
    {
        $img = new Imagick();
        $img->readImageBlob($image);

        if ($punch !== false) {
            $pnch = new Imagick();
            $pnch->readImageBlob($punch);
            
            $img->compositeImage($pnch, IMagick::COMPOSITE_DSTOUT, 0, 0);
            $pnch->destroy();
        }

        $ovl = new IMagick();
        $ovl->readImageBlob($overlay);
        $img->compositeImage($ovl, IMagick::COMPOSITE_DEFAULT, 0, 0);
        $ovl->destroy();

        $img->flattenImages();
        $img->setImageFormat('png32');

        $result = $img->getImageBlob();
        $img->destroy();

        return $result;
    }

    private function border($img, $distance = 1)
    {
        $image = imagecreatefromstring($img);
        $width  = imagesx($image);
        $height = imagesy($image);

        $new   = imagecreatetruecolor($width, $height);
        imagesavealpha($new, true);
        imagealphablending($new, true);
        imagefill($new, 0, 0, IMG_COLOR_TRANSPARENT);

        $color = array();
        for ($i = 0; $i <= 127; $i += 32) {
            $color[$i] = imagecolorallocatealpha($new, 0x00, 0x00, 0x00, $i);
        }

        $mask = array();
        for ($y = -$distance; $y <= $distance; $y += 1) {
            for ($x = -$distance; $x <= $distance; $x += 1) {
                $mask[] = array($x, $y);
            }
        }

        for ($y = 0; $y < $height; $y += 1) {
            for ($x = 0; $x < $width; $x += 1) {
                $pixel = imagecolorat($image, $x, $y);
                $alpha = ($pixel & 0x7f000000) >> 24;
                if ($alpha !== 127) {
                    foreach ($mask as $v) {
                        if (($x + $v[0] >= 0 && $x + $v[0] < $width) && ($y + $v[1] >= 0 && $y + $v[1] < $height)) {
                            imagesetpixel($new, $x + $v[0], $y + $v[1], $color[$alpha - $alpha % 32]);
                        }
                    }
                }
            }
        }

        ob_start();
        imagepng($new);
        $result = ob_get_clean();

        return $result;
    }
}
