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
    use DeprecatedIcon;

    const SVG = 1;
    const PNG = 2;
    const CSS_BACKGROUND = 4;
    const INPUT = 256;

    const DEFAULT_SIZE = 16;
    const DEFAULT_COLOR = 'blue';
    const DEFAULT_ROLE = 'clickable';

    protected $shape;
    protected $role;
    protected $attributes = array();


    /**
     * This is the magical Role to Color mapping.
     */
    private static $roles_to_colors = [
        'info'          => 'black',
        'clickable'     => 'blue',
        'link'          => 'blue',
        'accept'        => 'green',
        'status-green'  => 'green',
        'inactive'      => 'grey',
        'navigation'    => 'lightblue',
        'new'           => 'red',
        'attention'     => 'red',
        'status-red'    => 'red',
        'info_alt'      => 'white',
        'sort'          => 'yellow',
        'status-yellow' => 'yellow'
    ];

    // return the color associated to a role
    private static function roleToColor($role)
    {
        if (!isset(self::$roles_to_colors[$role])) {
            throw new \InvalidArgumentException('Unknown role: "' . $role . '"');
        }
        return self::$roles_to_colors[$role];
    }

    // return the roles! associated to a color
    private static function colorToRoles($color)
    {
        static $colors_to_roles;

        if (!$colors_to_roles) {
            foreach (self::$roles_to_colors as $r => $c) {
                $colors_to_roles[$c][] = $r;
            }
        }

        if (!isset($colors_to_roles[$color])) {
            throw new \InvalidArgumentException('Unknown color: "' . $color . '"');
        }

        return $colors_to_roles[$color];
    }

    /**
     * Create a new Icon object.
     *
     * This is just a factory method. You could easily just call the
     * constructor instead.
     *
     * @param String $shape      Shape of the icon, may contain a mixed definition
     *                           like 'seminar+add'
     * @param String $role       Role of the icon, defaults to Icon::DEFAULT_ROLE
     * @param Array $attributes  Additional attributes like 'title';
     *                           only use semantic ones describing
     *                           this icon regardless of its later
     *                           rendering in a view
     * @return Icon object
     */
    public static function create($shape, $role = Icon::DEFAULT_ROLE, $attributes = array())
    {
        // $role may be omitted
        if (is_array($role)) {
            $attributes = $role;
            $role = Icon::DEFAULT_ROLE;
        }

        return new self($shape, $role, $attributes);
    }

    /**
     * Constructor of the object.
     *
     * @param String $shape      Shape of the icon, may contain a mixed definition
     *                           like 'seminar+add'
     * @param String $role       Role of the icon, defaults to Icon::DEFAULT_ROLE
     * @param Array $attributes  Additional attributes like 'title';
     *                           only use semantic ones describing
     *                           this icon regardless of its later
     *                           rendering in a view
     */
    public function __construct($shape, $role = Icon::DEFAULT_ROLE, array $attributes = array())
    {

        // only defined roles
        if (!isset(self::$roles_to_colors[$role])) {
            throw new \InvalidArgumentException('Creating an Icon without proper role: "' . $role . '"');
        }

        // only semantic attributes
        if ($non_semantic = array_filter(array_keys($attributes), function ($attr) {
            return !in_array($attr, ['title']);
        })) {
            // DEPRECATED
            // TODO starting with the v3.6 the following line should
            // be enabled to prevent non-semantic attributes in this position
            # throw new \InvalidArgumentException('Creating an Icon with non-semantic attributes:' . json_encode($non_semantic));
        }

        $this->shape      = $shape;
        $this->role       = $role;
        $this->attributes = $attributes;
    }

    /**
     * Returns the `shape` -- the string describing the shape of this instance.
     * @return String  the shape of this Icon
     */
    public function getShape()
    {
        return $this->shape;
    }

    /**
     * Returns the `role` -- the string describing the role of this instance.
     * @return String  the role of this Icon
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Returns the semantic `attributes` of this instance, e.g. the title of this Icon
     * @return Array  the semantic attribiutes of the Icon
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Function to be called whenever the object is converted to
     * string. Internally the same as calling Icon::asImg
     *
     * @return String representation
     */
    public function __toString()
    {
        return $this->asImg();
    }

    /**
     * Renders the icon inside an img html tag.
     *
     * @param int   $size             Optional; Defines the dimension in px of the rendered icon; FALSE prevents any
     *                                width or height attributes
     * @param Array $view_attributes  Optional; Additional attributes to pass
     *                                into the rendered output
     * @return String containing the html representation for the icon.
     */
    public function asImg($size = null, $view_attributes = [])
    {
        if (is_array($size)) {
            list($view_attributes, $size) = [$size, null];
        }
        return sprintf('<img %s>',
                       $this->tag_options($this->prepareHTMLAttributes($size, $view_attributes)));
    }

    /**
     * Renders the icon inside an input html tag.
     *
     * @param int   $size             Optional; Defines the dimension in px of the rendered icon; FALSE prevents any
     *                                width or height attributes
     * @param Array $view_attributes  Optional; Additional attributes to pass
     *                                into the rendered output
     * @return String containing the html representation for the icon.
     */
    public function asInput($size = null, $view_attributes = [])
    {
        if (is_array($size)) {
            list($view_attributes, $size) = [$size, null];
        }
        return sprintf('<input type="image" %s>',
                       $this->tag_options($this->prepareHTMLAttributes($size, $view_attributes)));
    }

    /**
     * Renders the icon as a set of css background rules.
     *
     * @param int $size  Optional; Defines the size in px of the rendered icon
     * @return String containing the html representation for css backgrounds
     */
    public function asCSS($size = null)
    {
        if ($this->isStatic()) {
            return sprintf('background-image:url(%1$s);background-size:%2$upx %2$upx;',
                           $this->shapeToPath($this->shape),
                           $this->get_size($size));
        }

        return sprintf('background-image:url(%1$s);background-image:none,url(%2$s);background-size:%3$upx %3$upx;',
                       $this->get_asset_png($size),
                       $this->get_asset_svg(),
                       $this->get_size($size));
    }

    /**
     * Returns a path to the SVG matching the icon.
     *
     * @param int $size  Defines the size in px of the rendered icon
     * @return String containing the html representation for css backgrounds
     */
    public function asImagePath($size = null)
    {
        return $this->prepareHTMLAttributes($size, [])['src'];
    }

    /**
     * Returns a new Icon that contains the mapping of `$key` to `$val`.
     * @param string  $key  Name of the property, either "shape", "role" or "attributes"
     * @param mixed   $val  New value of that property
     * @return Icon  A new Icon containing the mapping of `$key` to `$val`
     */
    public function assoc($key, $val)
    {
        if (!in_array($key, ['shape', 'role', 'attributes'])) {
            throw new \BadMethodCallException(
                sprintf('Unknown key. Method was called with "%s" '.
                        'but expected either "shape", "role" or "attributes".', $key));
        }

        extract(get_object_vars($this));
        $$key = $val;

        return new self($shape, $role, (array) $attributes);
    }

    /**
     * Prepares the html attributes for use assembling HTML attributes
     * from given shape, role, size, semantic and view attributes
     *
     * @param int   $size       Size of the icon
     * @param array $attributes Additional attributes
     * @return Array containing the merged attributes
     */
    private function prepareHTMLAttributes($size, $attributes)
    {
        $dimensions = [];
        if ($size !== false) {
            $size = $this->get_size($size);
            $dimensions = ['width'  => $size, 'height' => $size];
        }

        $result = array_merge($this->attributes, $attributes, $dimensions, [
            'src' => $this->isStatic() ? $this->shape : $this->get_asset_svg(),
            'alt' => $this->attributes['alt'] ?: $this->attributes['title'] ?: basename($this->shape)
        ]);

        $classNames = 'icon-role-' . $this->role;

        if (!$this->isStatic()) {
            $classNames .= ' icon-shape-' . $this->shape;
        }

        $result['class'] = isset($result['class']) ? $result['class'] . ' ' . $classNames : $classNames;

        return $result;
    }

    /**
     * Get the correct asset for an SVG icon.
     *
     * @return String containing the url of the corresponding asset
     */
    protected function get_asset_svg()
    {
        return Assets::url('images/icons/' . self::roleToColor($this->role) . '/' . $this->shapeToPath($this->shape) . '.svg');
    }


    /**
     * Get the correct asset for a PNG icon.
     *
     * @param int $size  size of the icon
     * @return String containing the url of the corresponding asset
     */
    protected function get_asset_png($size)
    {
        $color = self::roleToColor($this->role);
        $size = $this->get_size($size);

        if ($GLOBALS['auth']->auth['devicePixelRatio'] > 1.2) {
            $size *= 2;
        }

        return Assets::url('images/icons/' . $size . '/' . $color . '/' . $this->shapeToPath($this->shape) . '.png');
    }

    /**
     * Get the size of the icon. If a size was passed as a parameter and
     * inside the attributes array during icon construction, the size from
     * the attributes will be used.
     *
     * @param int $size  size of the icon
     * @return int Size of the icon in pixels
     */
    protected function get_size($size)
    {
        // DEPRECATED
        // TODO remove deprecatedSize in v3.6
        $size = $size ?: $this->deprecatedSize ?: Icon::DEFAULT_SIZE;
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

    // an icon is static if it starts with 'http'
    private function isStatic()
    {
        return strpos($this->shape, 'http') === 0;
    }

    // transforms a shape w/ possible additions (`shape+addition`) to a path `(addition/)?shape`
    private function shapeToPath()
    {
        return $this->isStatic()
            ? $this->shape :
            join('/', array_reverse(explode('+', preg_replace('/\.(?:png|svg)$/', '', $this->shape))));
    }
}


// DEPRECATED
// TODO remove this trait in v3.6
trait DeprecatedIcon {

    protected $deprecatedSize = null;

    public static $icon_colors = array(
        'black', 'blue', 'green', 'grey', 'lightblue', 'red', 'white', 'yellow',
    );

    /**
     * @param String $source     Name of the icon, may contain a mixed definition
     *                           like 'icons/16/blue/add/seminar.png' due to
     *                           compatibility issues with Assets::img().
     * @param Array  $attributes Additional attributes to pass the rendered
     *                           output
     * @return Icon object
     */
    public static function create2($source, $attributes = array())
    {
        // external icon
        $source = str_replace(Assets::url('images/'), '', $source);
        if (strpos($source, 'http') === 0) {
            return new self($source, Icon::DEFAULT_ROLE, $attributes);
        }

        $opts = self::rearrange($source,
                                ['size' => Icon::DEFAULT_SIZE, 'color' => Icon::DEFAULT_COLOR]);

        $opts['source'] = preg_replace('/\.(png|svg)$/', '', $opts['icon']);

        // use the very first role matching this color
        $role = current(self::colorToRoles($opts['color']));

        $icon = new Icon($opts['source'], $role, $attributes);

        $icon->deprecatedSize = $opts['size'];

        return $icon;
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
        if ($type & Icon::SVG || $type & Icon::PNG) {
            return $type & Icon::INPUT ? $this->asInput() : $this->asImg();
        }
        if ($type & Icon::CSS_BACKGROUND) {
            return $this->asCSS();
        }
        throw new \Exception('Unknown type');
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
                return false;
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

}
