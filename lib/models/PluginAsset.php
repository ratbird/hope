<?php
/**
 * Database model for plugin assets
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL2 or any later version
 * @since   Stud.IP 3.4
 */
class PluginAsset extends SimpleORMap
{
    const CACHE_DURATION = 2419200; // = 4 * 7 * 24 * 60 * 60 = 4 weeks

    /**
     * Configures the model
     *
     * @param Array $config Configuration array
     */
    protected static function configure($config = array())
    {
        $config['db_table'] = 'plugin_assets';

        parent::configure($config);
    }

    /**
     * Store the asset's content. Currently, this will store
     * to a file in the upload directory of Stud.IP.
     *
     * @param String $content Content of the asset
     */
    public function writeContent($content)
    {
        $fp = $this->getFileHandle('w+');
        fputs($fp, $content);
        fclose($fp);

        if (strlen($content) != $this->size) {
            $this->size = strlen($content);
            $this->store();
        } else {
            $this->triggerChdate();
        }
    }

    /**
     * Read the asset's content. Currently, this will read
     * from a file in the upload directory of Stud.IP.
     *
     * @return String containing the asset's content
     */
    public function readContent()
    {
        $fp = $this->getFileHandle('r');
        $content = stream_get_contents($fp);
        fclose($fp);

        return $content;
    }

    /**
     * Deletes the asset.
     *
     * @return int indicating how many rows were deleted
     */
    public function delete()
    {
        $filename = $this->getFilepath();
        if (file_exists($filename)) {
            unlink($filename);
        }

        return parent::delete();
    }

    /**
     * Obtain a file handle
     *
     * @param String $mode Valid file mode for fopen().
     * @return Resource for the file
     * @throws Exception when handle could not be obtained
     */
    private function getFileHandle($mode)
    {
        $filename = $this->getFilepath();
        $fp = fopen($filename, $mode);
        if ($fp === false) {
            throw new Exception('Could not obtain file handle');
        }
        return $fp;
    }

    /**
     * Returns the path to the asset's file
     *
     * @return String containing the file path
     * @throws Exception when the asset path is invalid or could not
     *                   be created
     */
    private function getFilepath()
    {
        $assets_path = $GLOBALS['UPLOAD_PATH'] . '/assets';
        if ((!file_exists($assets_path) && !mkdir($assets_path)) || !is_dir($assets_path)) {
            throw new Exception('Unable to access assets directory');
        }

        return $assets_path . '/' . $this->storagename;
    }
}