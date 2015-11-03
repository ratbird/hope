<?php
/**
 * Wrapper class for the less compilation.
 * Currently uses ILess by mishal <https://github.com/mishal/iless>.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL2 or any later version
 */
class LESSCompiler
{
    const CACHE_KEY = '/less-compiler/prefix';

    private static $instance = null;

    /**
     * Returns an instance of the compiler.
     *
     * @return LESSCompiler instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private $parser;

    /**
     * Private constructor to enable a singleton pattern.
     */
    private function __construct()
    {
        ILess\Autoloader::register();

        $this->parser = new ILess\Parser([
            'strictMath' => true,
        ]);
        $this->parser->setVariables(array(
            'image-path' => '"' . Assets::url('images') . '"',
        ));
    }

    /**
     * Compiles a less file. This method will add all neccessary imports
     * and variables for Stud.IP so almost all mixins and variables of the
     * core system can be used. This includes colors and icons.
     *
     * @param String $filename LESS file to compile
     * @return String containing the generated CSS
     * @throws RuntimeException when the LESS file does not exist
     */
    public function compile($filename)
    {
        if (!file_exists($filename)) {
            $message = sprintf('Unable to locate less file "%s"',
                               $filename);
            throw new RuntimeException($message);
        }

        $less  = $this->getPrefix();
        $less .= file_get_contents($filename);

        $this->parser->parseString($less);
        return $this->parser->getCSS();
    }

    /**
     * Generates the less prefix containing the variables and mixins of the
     * Stud.IP core system.
     * This prefix will be cached in Stud.IP's cache in order to minimize
     * disk accesses.
     *
     * @return String containing the neccessary prefix
     */
    private function getPrefix()
    {
        $cache = StudipCacheFactory::getCache();

        $prefix = $cache->read(self::CACHE_KEY);
        if ($prefix === false) {
            $prefix = '';

            // Load mixins and change relative to absolute filenames
            $mixin_file = $GLOBALS['ABSOLUTE_PATH_STUDIP'] . 'assets/stylesheets/mixins.less';
            foreach (file($mixin_file) as $mixin) {
                if (!preg_match('/@import(.*?) "(.*)";/', $mixin, $match)) {
                    continue;
                }

                $core_file = $GLOBALS['ABSOLUTE_PATH_STUDIP'] . '/assets/stylesheets/' . $match[2];
                $prefix .= sprintf('@import%s "%s";' . "\n", $match[1], $core_file);
            }

            // Add adjusted image paths
            $prefix .= sprintf('@image-path: "%s";', Assets::url('images')) . "\n";
            $prefix .= '@icon-path: "@{image-path}/icons/16";' . "\n";

            $cache->write(self::CACHE_KEY, $prefix);
        }
        return $prefix;
    }
}
