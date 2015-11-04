<?php
namespace Assets;

use RuntimeException;

use Assets;
use StudipCacheFactory;

use ILess\Autoloader;
use ILess\Parser;

/**
 * Wrapper class for the assets compilation. Supports LESS
 * complilation by now.
 * 
 * Currently uses ILess by mishal <https://github.com/mishal/iless>.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL2 or any later version
 * @since   Stud.IP 3.4
 */
class Compiler
{
    const CACHE_KEY_LESS = '/assets/less-prefix';

    private static $factory = null;

    /**
     * Returns an instance of the compiler.
     *
     * @return LESS\Compiler instance
     */
    public static function getInstance()
    {
        if (self::$factory === null) {
            $factory = new PluginAssetFactory();
            self::setFactory($factory);
        }
        return new self();
    }

    /**
     * Sets a file factory to use. If no file factory will be set,
     * the default PluginAssetFactory will be used.
     *
     * @param LESS\FileFactory $factory The factory
     */
    public static function setFactory(AssetFactory $factory = null)
    {
        self::$factory = $factory;
    }

    private $parser;
    private $metadata = array();

    /**
     * Private constructor.
     */
    private function __construct()
    {
    }

    /**
     * Set metadata for the compiler.
     *
     * @param Array $metadata The metdata as an associative array
     */
    public function setMetaData(array $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Compiles a less file. This method will add all neccessary imports
     * and variables for Stud.IP so almost all mixins and variables of the
     * core system can be used. This includes colors and icons.
     *
     * @param String $filename LESS file to compile
     * @return LESS\File file containing the generated CSS
     * @throws RuntimeException when the LESS file does not exist
     */
    public function compileLESS($filename)
    {
        if (!file_exists($filename)) {
            $message = sprintf('Unable to locate less file "%s"',
                               $filename);
            throw new RuntimeException($message);
        }

        $file = self::$factory->createCSSFile($filename, $this->metadata);
        $file->setOriginalFilename($filename);
        if ($file->isNew()) {
            $less  = $this->getLESSPrefix();
            $less .= file_get_contents($filename);

            $parser = $this->getLESSParser();
            $parser->parseString($less);
            $css = $parser->getCSS();

            $file->setContent($css);
        }
        return $file;
    }

    /**
     * Returns CSS parser instance.
     *
     * @return ILess\Parser instance
     */
    private function getLESSParser()
    {
        Autoloader::register(); 

        $parser = new Parser([
            'strictMath' => true,
        ]);
        $parser->setVariables(array(
            'image-path' => '"' . Assets::url('images') . '"',
        ));
        return $parser;
    }

    /**
     * Generates the less prefix containing the variables and mixins of the
     * Stud.IP core system.
     * This prefix will be cached in Stud.IP's cache in order to minimize
     * disk accesses.
     *
     * @return String containing the neccessary prefix
     */
    private function getLESSPrefix()
    {
        $cache = StudipCacheFactory::getCache();

        $prefix = $cache->read(self::CACHE_KEY_LESS);
        if ($prefix === false) {
            $prefix = '';

            // Load mixins and change relative to absolute filenames
            $mixin_file = $GLOBALS['ABSOLUTE_PATH_STUDIP'] . 'assets/stylesheets/mixins.less';
            foreach (file($mixin_file) as $mixin) {
                if (!preg_match('/@import(.*?) "(.*)";/', $mixin, $match)) {
                    continue;
                }

                $core_file = $GLOBALS['ABSOLUTE_PATH_STUDIP'] . 'assets/stylesheets/' . $match[2];
                $prefix .= sprintf('@import%s "%s";' . "\n", $match[1], $core_file);
            }

            // Add adjusted image paths
            $prefix .= sprintf('@image-path: "%s";', Assets::url('images')) . "\n";
            $prefix .= '@icon-path: "@{image-path}/icons/16";' . "\n";

            $cache->write(self::CACHE_KEY_LESS, $prefix);
        }
        return $prefix;
    }
}
