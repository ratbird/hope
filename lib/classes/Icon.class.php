<?php
/**
 * Icon class is used to create icon objects which can be rendered as
 * svg or png. Output will be html. Optionally, the icon can be rendered
 * as a css background.
 *
 * @author    Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @copyright Stud.IP Core Group
 * @license   GPL2 or any later version
 * @since     3.2
 */
class Icon
{
    const SVG = 1;
    const PNG = 2;
    const CSS_BACKGROUND = 4;

    const DEFAULT_SIZE = 16;
    const DEFAULT_COLOR = 'blue';

    public static $icon_colors = array(
        'black', 'blue', 'green', 'grey', 'lightblue', 'red', 'white', 'yellow',
    );

    /**
     * @param String $source Name of the icon, may contain a mixed definition
     *                       like 'icons/16/blue/add/seminar.png' due to
     *                       compatibility issues with Assets::img().
     * @param int    $size   Size of the icon, defaults to fixed default icon
     *                       size
     * @param String $color  Color of the icon, defaults to fixed default icon
     *                       color
     * @param mixed  $icon   Extra icon to apply to the icon, default is none
     * @param Array  $attributes Additional attributes to pass the rendered
     *                           output
     * @return Icon object
     */
    public static function create($source, $size = Icon::DEFAULT_SIZE, $color = Icon::DEFAULT_COLOR, $icon = false, $attributes = array())
    {
        // Extend arguments if not all are given
        if (func_num_args() === 2 && is_array($size)) {
            $attributes = $size;
            $size = Icon::DEFAULT_SIZE;
        } else if (func_num_args() === 3 && is_array($color)) {
            $attributes = $color;
            $color = Icon::DEFAULT_COLOR;
        } else if (func_num_args() === 4 && is_array($icon)) {
            $attributes = $icon;
            $icon = false;
        }

        // Try to guess correct order of passed arguments
        $defined = array_filter(compact(array('size', 'color', 'icon')));
        $defined = self::rearrange($defined);
        $icon = $defined['icon'];
        unset($defined['icon']);

        $opts = self::rearrange($source, $defined, $icon);

        $opts['source'] = preg_replace('/\.(png|svg)$/', '', $opts['icon']);

        return new self($opts['source'], $opts['size'], $opts['color'], $attributes);
    }

    /**
     * Rearranges passed parameters. Tries to detect given size, color and
     * extra icon.
     *
     * @param mixed $input    Either a relative or absolute url or an array
     * @param Array $defaults Default values for size, color and extra icon
     * @param mixed $extra    Extra icon to apply to the icon, defaults to none
     * @return Array with the guessed values
     */
    protected static function rearrange($input, $defaults = array(), $extra = false)
    {
        if (!is_array($input)) {
            $input = str_replace(Assets::url('images/'), '', $input);
            if (strpos($input, 'http') !== false) {
                echo '<pre>';var_dump($input, Assets::url('images/'));die;
            }
            $input = preg_replace('~^icons/~S', '', $input);
            $input = preg_replace('/\.png$/S', '', $input);
            $input = explode('/', $input);
        }
        
        $result = array_merge(array(
            'size' => Icon::DEFAULT_SIZE,
            'color' => Icon::DEFAULT_COLOR,
            'icon' => array(),
        ), $defaults); 

        foreach ($input as $chunk) {
            if (is_int($chunk) || ctype_digit($chunk)) {
                $result['size'] = $chunk;
            } elseif (in_array($chunk, self::$icon_colors)) {
                $result['color'] = $chunk;
            } else {
                $result['icon'][] = $chunk;
            }
        }

        if (count($result['icon']) === 1 && $extra) {
            array_unshift($result['icon'], $extra);
        }

        $result['icon'] = join('/', $result['icon']);
        
        return $result;
    }

    protected $icon;
    protected $size;
    protected $color;
    protected $attributes;

    /**
     * Constructor of the object.
     *
     * @param String $source Name of the icon, may contain a mixed definition
     *                       like 'icons/16/blue/add/seminar.png' due to
     *                       compatibility issues with Assets::img().
     * @param int    $size   Size of the icon, defaults to fixed default icon
     *                       size
     * @param String $color  Color of the icon, defaults to fixed default icon
     *                       color
     * @param Array  $attributes Additional attributes to pass the rendered
     *                           output
     */
    public function __construct($icon, $size = Icon::DEFAULT_SIZE, $color = Icon::DEFAULT_COLOR, $attributes = array())
    {
        $this->icon       = preg_replace('/\.(?:png|svg)$/', '', $icon);
        $this->size       = $size;
        $this->color      = $color;
        $this->attributes = $attributes;
    }

    /**
     * Function to be called whenever the object is converted to string.
     *
     * @return String representation (defaults to svg rendering)
     */
    public function __toString()
    {
        return $this->render_svg();
    }

    /**
     * Renders the icon as svg, png or css background.
     *
     * @param int $type Defines in which manner the icon should be rendered,
     *                  defaults to svg.
     * @return String containing the rendered output
     * @throws Exception if no valid type was passed
     */
    public function render($type = Icon::SVG)
    {
        if ($type === Icon::SVG) {
            return $this->render_svg();
        }
        if ($type === Icon::PNG) {
            return $this->render_png();
        }
        if ($type === Icon::CSS_BACKGROUND) {
            return $this->render_css_background();
        }
        throw new Exception('Unknown type');
    }

    /**
     * Renders the icon inside a svg html tag.
     *
     * @return String containing the html representation for svg.
     */
    protected function render_svg()
    {
        $png_attributes = array(
            'xlink:href' => $this->get_asset(Icon::SVG),
            'src' => $this->get_asset(Icon::PNG),
            'alt' => $this->attributes['alt'] ?: $this->attributes['title'] ?: basename($this->icon),
            'width'  => $this->get_size(),
            'height' => $this->get_size(),
        );
        unset($this->attributes['alt'], $this->attributes['src']);

        $svg_attributes = array_merge($this->attributes, array(
            'width'  => $this->get_size(),
            'height' => $this->get_size(),
        ));

        return sprintf('<svg %s><image %s></svg>',
                              $this->tag_options($svg_attributes),
                              $this->tag_options($png_attributes));
    }

    /**
     * Renders the icon inside a img html tag as png.
     *
     * @return String containing the html representation for png.
     */
    protected function render_png()
    {
        $attributes = array_merge($this->attributes, array(
            'src' => $this->get_asset(Icon::PNG),
            'alt' => $this->attributes['alt'] ?: $this->attributes['title'] ?: basename($this->icon),
            'width'  => $this->get_size(),
            'height' => $this->get_size(),
        ));
        
        return sprintf('<img %s>', $this->tag_options($attributes));
    }

    /**
     * Renders the icon as a set of css background rules.
     *
     * @return String containing the html representation for css backgrounds
     */
    protected function render_css_background()
    {
        return sprintf('background-image:url(%1$s);background-image:none,url(%2$s);background-size:%3$upx %3$upx;',
                       $this->get_asset(Icon::PNG),
                       $this->get_asset(Icon::SVG),
                       $this->get_size());
    }

    /**
     * Get the correct asset for the icon.
     *
     * @param int $type Either svg or png, no other assets are defined.
     * @return String containing the url of the corresponding asset
     * @throws Exception if an invalid type was passed
     */
    protected function get_asset($type)
    {
        if ($type === Icon::SVG) {
            return Assets::url('images/icons/' . $this->color . '/' . $this->icon . '.svg');
        }
        if ($type === Icon::PNG) {
            $size = $this->size;
            if ($GLOBALS['auth']->auth['devicePixelRatio'] > 1.2) {
                $size *= 2;
            }
            return Assets::url('images/icons/' . $size . '/' . $this->color . '/' . $this->icon . '.png');
        }
        throw new Exception('Unknown type');
    }

    /**
     * Get the size of the icon. If a size was passed as a parameter and
     * inside the attributes array during icon construction, the size from
     * the attributes will be used.
     *
     * @return int Size of the icon in pixels
     */
    protected function get_size()
    {
        $size = $this->size;
        if (isset($this->attributes['size'])) {
            list($size, $temp) = explode('@', $this->attributes['size'], 2);
            unset($this->attributes['size']);
        }
        return (int)$size;
    }

    /**
     * Renders an array of options as html attributes.
     *
     * @param Array $options 1-dimensional associative array of options
     * @return String containing the representation of the options as html tag
     *         attributes.
     */
    protected function tag_options($options)
    {
        $result = array();
        foreach ($options as $key => $value) {
            $result[] = sprintf('%s="%s"', $key, addcslashes($value, '"'));
        }
        return join(' ', $result);
    }
}