<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
require_once('lib/raumzeit/decorator/Decorator.class.php');

class RoomDecorator extends Decorator {
	var $termin = NULL;
	var $xml_export = FALSE;
	var $link = FALSE;

	function RoomDecorator($data) {
		parent::Decorator($data);
	}

	function toString() {
		global $RESOURCES_ENABLE;
		
		$termin =& $this->termin;

		if ($RESOURCES_ENABLE && ($raum = $this->termin->getResourceID())) {
			$resObj =& ResourceObject::Factory($raum);
			if ($this->link) {
				$raum = $resObj->getFormattedLink(TRUE, TRUE, TRUE);
			} else {
				$raum = $resObj->getName();
			}
		} else {
			if (!($raum = $this->termin->getFreeRoomText())) $raum = _("k.A."); else $raum = $this->xml_export? $raum : '('.$raum.')';
		}

		if ($this->xml_export) {
			$xml .= sprintf('<raumzeit><datum>%s</datum><zeit>%s</zeit><raum>%s</raum></raumzeit>', date('d.m.Y', $this->termin->getStartTime()), $zeit, $raum);
		}
		return ($this->xml_export) ? $xml : $raum;

	}
}
?>
