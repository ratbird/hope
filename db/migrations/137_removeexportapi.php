<?
Class Removeexportapi extends Migration {

    function description()
    {
        return 'Removes export api';
    }


    function up()
    {
        DBManager::get()->exec("DROP TABLE `export_templates`");
    }

    function down()
    {
    }
}
