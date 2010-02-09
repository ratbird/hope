<?
class RemoveFieldsFromExtermine extends DBMigration {

	function description () {
		return 'removes expire|repeat|color|priority from table ex_termine';
	}

	function up () {
		$this->announce(" removing fields...");
		
		$this->db->query("ALTER TABLE `ex_termine` DROP `expire`, DROP `repeat`, DROP `color`, DROP `priority`");

		$this->announce("done.");
		
	}
	
	function down () {
		$this->announce("done.");
	}
}
?>
