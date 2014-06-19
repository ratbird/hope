<?php
class ActivityFeedEnabled extends Migration
{
    public function up()
    {
       $db = DBManager::get();
        $time = time();

        $stmt = $db->prepare("
            INSERT INTO config
                (config_id, field, value, is_default, type, section, mkdate, chdate, description)
            VALUES
                (MD5(:name), :name, :value, 1, :type, :section, $time, $time, :description)
        ");

        $stmt->execute(array(
            'name' => 'ACTIVITY_FEED_WIDGET_ENABLED',
            'description' => 'Erlaubt Nutzern, die globale Aktivitätsübersicht als Feed zu exportieren.',
            'section'     => 'global',
            'type'        => 'boolean',
            'value'       => 0
        ));
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("DELETE FROM config WHERE field = 'ACTIVITY_FEED_WIDGET_ENABLED'");
    }
}
?>
