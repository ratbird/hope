<?

require_once("lib/functions.php");
require_once($GLOBALS["RELATIVE_PATH_EXTERN"]."/extern_config.inc.php");
require_once($GLOBALS["RELATIVE_PATH_EXTERN"]."/lib/ExternConfig.class.php");
require_once($GLOBALS["RELATIVE_PATH_EXTERN"]."/lib/ExternConfigIni.class.php");
require_once($GLOBALS["RELATIVE_PATH_EXTERN"]."/lib/ExternConfigDb.class.php");


class Step87ExternConfigurations extends DBMigration {

	function description () {
		return 'Extends table extern_config and converts configurations for the external pages from INI-style files to serialised arrays stored in the database.';
	}

	function up () {
		$this->db->query("
			ALTER TABLE `extern_config` ADD `config` MEDIUMTEXT NOT NULL AFTER `is_standard`
		");
		
		$this->db->query("SELECT * FROM extern_config");
		
		$this->announce(" KONVERTIERUNG START ");
		
		$i = 0;
		while ($this->db->next_record()) {
			
			
			$old_config =& new ExternConfigIni($this->db->f('range_id'), '', $this->db->f('config_id'));
			$new_config =& new ExternConfigDb($this->db->f('range_id'), '', $this->db->f('config_id'));
			
	
			$new_config->setConfiguration($old_config->getConfiguration());
	
			if ($new_config->store()) {
				$this->write(sprintf("Konfiguration mit der id %s konvertiert!", $new_config->getId()));
				$i++;
			} else {
				$this->write(sprintf("FEHLER! Die Konfiguration mit der id %s konnte nicht konvertiert werden!", $this->db->f('config_id')));
			}
		}

		if ($this->db->num_rows() == $i) {
			$this->write("Alle Konfigurationsdateien vermutlich fehlerfrei in die Datenbank uebertragen!");
			$this->write(sprintf("Es wurden %s Konfigurationsdateien uebertragen.", $i));
		} else {
			$this->write("Es wurden nicht alle Konfigurationsdateien uebertragen!");
			$this->write(sprintf("Es wurden %s Konfigurationsdateien von %s Konfigurationsdateien uebertragen!", $i, $this->db->num_rows()));
			$this->write("Bitte die fehlerhaften Konfigurationen manuell ueberpruefen.");
		}
		
		$this->announce(" KONVERTIERUNG ENDE ");
		
	}
	
}
?>
