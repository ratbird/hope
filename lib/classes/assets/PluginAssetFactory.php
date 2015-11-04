<?php
namespace Assets;

use PluginAsset as AssetModel;

/**
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL2 or any later version
 * @since   Stud.IP 3.4
 */
class PluginAssetFactory implements AssetFactory
{
    public function createCSSFile($filename, array $metadata = array())
    {
        $hash = md5(implode('|', array(
            $metadata['plugin_id'] ?: 'unknown',
            $metadata['plugin_version'] ?: '0.0',
            $filename
        )));

        $asset = new AssetModel($hash);
        if ($asset->isNew()) {
            $css_filename = sprintf('%s.%s.css',
                                    basename($filename, '.less'),
                                    $metadata['plugin_version']);

            $asset->plugin_id   = $metadata['plugin_id'];
            $asset->type        = 'css';
            $asset->filename    = $css_filename;
            $asset->storagename = $hash . '.css';
            $asset->size        = null;
            $asset->store();
        }
        return new PluginAsset($asset);
    }
}
