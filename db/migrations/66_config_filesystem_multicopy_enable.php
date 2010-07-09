<?php

class ConfigFilesystemMulticopyEnable extends Migration {
    
    function description() {
        return 'Inserts a new config-variable to enable or disable multicopy for teachers.';
    }

    function up() {
        $config = Config::get();
        if (!isset($config['FILESYSTEM_MULTICOPY_ENABLE'])) {
            $config->create('FILESYSTEM_MULTICOPY_ENABLE', array(
                'type' => "boolean",
                'field' => 'FILESYSTEM_MULTICOPY_ENABLE', 
                'value' => true, 
                'section' => "modules", 
                'description' => "Soll es erlaubt sein, das Dozenten Ordner oder Dateien in mehrere Veranstaltungen bzw. Institute verschieben oder kopieren dürfen?", 
                'is_default' => true));
        }
    }

    function down() {
        Config::get()->delete('FILESYSTEM_MULTICOPY_ENABLE');
    }
}
