<?
# Lifter002: TODO
# Lifter010: TODO
/**
* Adapter for using php5 ext:soap with Ilias3Soap
*
*
* @author   Andre Noack <noack@data-quest.de>
* @access   public
* @package  ELearning-Interface
*/
class StudipSoapClient
{
    var $soap_client;
    var $error;

    function StudipSoapClient($path)
    {
        $this->getCorrectedWsdl($path);
        try{
            $this->soap_client = new SoapClient($GLOBALS['TMP_PATH'] . '/' . md5($path) . '.wsdl', array('trace' => 0));
        } catch (SoapFault $fault){
            $this->error = "<b>Soap Constructor Error</b><br>" . $fault->faultcode . ": ".$fault->faultstring."<br><br>";
        }
    }

    function call($method, $params)
    {
        $this->faultstring = "";
        $this->soap_client->_cookies = array();
        try{
            $result = $this->soap_client->__soapCall($method, $params);
        } catch  (SoapFault $fault){
            $this->faultstring = $fault->faultstring;
            if (!in_array($this->faultstring, array("Session not valid","Session Invalid")))
                $this->error .= "<hr><font size=\"-1\"><b>" . sprintf(_("SOAP-Fehler, Funktion \"%s\":"), $method) . "</b> " . $fault->faultstring . " (" .  $fault->faultcode . ")<br>".print_r($params,1).'</font><hr>';
                error_log($this->error);
                $this->soap_client->fault = true;
            return false;
        }
        if (is_object($result)) $result = (array)$result;
        if (is_array($result)){
            foreach($result as $index => $one){
                if (is_object($one)) $result[$index] = (array)$one;
                if (is_array($result[$index])){
                    //hmmm
                } else {
                    $result[$index] = studip_utf8decode($result[$index]);
                }
            }
        } else {
            $result = studip_utf8decode($result);
        }
        $this->soap_client->fault = false;
        return $result;
    }

    function getError()
    {
         $error = $this->error;
         $this->error = "";
         if ($error != "")
             return $error;
        else
            return false;
    }

    function getCorrectedWsdl($path){
        $correct_elements = array (
        0 => 'usr_id',
        1 => 'institution',
        2 => 'street',
        3 => 'city',
        4 => 'zipcode',
        5 => 'country',
        6 => 'phone_office',
        7 => 'last_login',
        8 => 'last_update',
        9 => 'create_date',
        10 => 'hobby',
        11 => 'department',
        12 => 'phone_home',
        13 => 'phone_mobile',
        14 => 'fax',
        15 => 'time_limit_owner',
        16 => 'time_limit_unlimited',
        17 => 'time_limit_from',
        18 => 'time_limit_until',
        19 => 'time_limit_message',
        20 => 'referral_comment',
        21 => 'matriculation',
        22 => 'active',
        23 => 'accepted_agreement',
        24 => 'approve_date',
        25 => 'user_skin',
        26 => 'user_style',
        27 => 'user_language',
        28 => 'import_id'
        );

        //grmpf. !$this->cms_type == aufruf von admin_elearning_interface.php
        if(!$this->cms_type) @unlink($GLOBALS['TMP_PATH'] . '/' . md5($path) . '.wsdl');

        if($path && !file_exists($GLOBALS['TMP_PATH'] . '/' . md5($path) . '.wsdl')){
            $wsdl = simplexml_load_file($path);
            $xsd = $wsdl->types->children('http://www.w3.org/2001/XMLSchema');
            foreach($xsd->schema->complexType as $ct){
                if ($ct->attributes()->name == 'ilUserData') break;
            }
            foreach($ct->all->element as $element){
                if( in_array($element->attributes()->name, $correct_elements) && !isset($element->attributes()->minOccurs) ){
                    $element->addAttribute('minOccurs','0');
                }
            }
            $wsdl->asXml($GLOBALS['TMP_PATH'] . '/' . md5($path) . '.wsdl');
        }
    }
}
?>
