<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Biest4749RemoveRssExport extends Migration {
    
    function description() {
        return 'Removes RSS Export Configuration';
    }

    function up() {
        Config::get()->delete('NEWS_RSS_EXPORT_ENABLE ');
        
    }
    
    function down() {
        Config::get()->create('NEWS_RSS_EXPORT_ENABLE ', array(
            'value' => 0,
            'is_default' => 0,
            'type' => 'boolean',
            'range' => 'global',
            'section' => 'global',
            'description' => _('Schaltet die M?glichkeit des rss-Export von privaten News global ein oder aus')
        ));
    }

}


