<?php
class Tic4993AddWysiwygHtmlHeuristicConfig extends Migration
{
    public function description()
    {
        return 'Adds the config entry "WYSIWYG_HTML_HEURISTIC_FALLBACK" that indicates '
             . 'whether the HTML heuristic should be used to detect mixed content'
             . '(Stud.IP Markup and HTML).';
    }
    
    public function up()
    {
        $name = 'WYSIWYG_HTML_HEURISTIC_FALLBACK';
        $description = 'Aktiviert die Heuristik um automatisch vermischte Inhalte (Stud.IP Markup '
                      .'und HTML) zu erkennen. Diese Option sollte nur bei Installationen aktiviert'
                      .' werden, die den WYSIWYG-Editor bereits vor Stud.IP Version 3.3 aktiviert '
                      .'haben.';
        
        /**
         * insert default value: heuristic is disabled.
         */
        $options[] = array(
            'config_id'   => md5($name),
            'name'        => $name,
            'type'        => 'boolean',
            'value'       => '0',
            'is_default'  => '1',
            'range'       => 'global',
            'section'     => 'global',
            'description' => $description,
        );
        
         /* 
         * determine whether the heuristic should be enabled in this Stud.IP installation:
         * - if WYSIWYG was enabled before: there is maybe mixed content available: enable heuristic
         */
        if (Config::get()->WYSIWYG) {
            $options[] = array(
                'config_id'   => 'cfcd208495d565ef66e7dff9f98764da',
                'name'        => $name,
                'type'        => 'boolean',
                'value'       => '1',
                'is_default'  => '0',
                'range'       => 'global',
                'section'     => 'global',
                'description' => $description,
            );
            
        }
        
        $stmt = DBManager::get()->prepare("
                INSERT INTO config
                    (config_id, field, value, is_default, type, `range`, section, mkdate, chdate, description)
                VALUES
                    (:config_id, :name, :value, :is_default, :type, :range, :section, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)
                ");

        foreach ($options as $option) {
            $stmt->execute($option);
        }
        
    }
    
    public function down()
    {
        $db = DBManager::get();
        $db->exec("DELETE FROM config WHERE field = 'WYSIWYG_HTML_HEURISTIC_FALLBACK'");
    }
}
