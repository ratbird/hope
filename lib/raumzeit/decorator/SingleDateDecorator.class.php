<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
require_once('lib/raumzeit/decorator/Decorator.class.php');

class SingleDateDecorator extends Decorator {
    var $termin = NULL;
    var $xml_export = FALSE;
    var $link = FALSE;

    function SingleDateDecorator($data) {
        parent::Decorator($data);
    }

    function toString() {
        global $RESOURCES_ENABLE;
        
        $termin =& $this->termin;

        if ($RESOURCES_ENABLE && ($raum = $this->termin->getResourceID())) {
            $resObj = ResourceObject::Factory($raum);
            if ($this->link) {
                $raum = $resObj->getFormattedLink(TRUE, TRUE, TRUE);
            } else {
                $raum = $resObj->getName();
            }
        } else {
            if (!($raum = $this->termin->getFreeRoomText())) $raum = _("k.A."); else $raum = $this->xml_export? $raum : '('.$raum.')';
        }

        $zeit = date('H:i', $this->termin->getStartTime()).'-'.date('H:i', $this->termin->getEndTime());

        $ret = '<table cellspacing="0" cellpadding="1" border="0" width="100%">';
        $ret .= '<tr><td width="40%"><font size="-1">'.getWeekDay(date('w', $this->termin->getStartTime())).'. '.date('d.m. Y', $this->termin->getStartTime()).'</font></td>';
        $ret .= '<td width="30%"><font size="-1">'.$zeit.'</font></td>';
        $ret .= '<td width="20%"><font size="-1">&nbsp;&nbsp;'.$raum.'</font></td></tr>';
        $ret .= '</table>';

        if ($this->xml_export) {
            $xml .= sprintf('<raumzeit><datum>%s</datum><zeit>%s</zeit><raum>%s</raum></raumzeit>', date('d.m.Y', $this->termin->getStartTime()), $zeit, $raum);
        }
        return ($this->xml_export) ? $xml : $ret;

    }
}
?>
