<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO
require_once("Ilias3Soap.class.php");

/**
 * class to use ILIAS-4-Webservices
 *
 * This class contains methods to connect to the ILIAS-4-Soap-Server.
 *
 * @author    Arne Schröder <schroeder@data-quest.de>
 * @access    public
 * @modulegroup    elearning_interface_modules
 * @module        Ilias4Soap
 * @package    ELearning-Interface
 */
class Ilias4Soap extends Ilias3Soap
{
    var $cms_type;
    var $admin_sid;
    var $user_sid;
    var $user_type;
    var $soap_cache;
    var $separator_string;

    /**
     * constructor
     *
     * init class.
     * @access
     * @param string $cms system-type
     */
    function Ilias4Soap($cms)
    {
        parent::Ilias3Soap($cms);
        $this->seperator_string = " &raquo; ";
    }

    /**
     * add user by importUsers
     *
     * adds new user and sets role-id
     * @access public
     * @param array user_data user-data
     * @param string role_id global role-id for new user
     * @return string user-id
     */
    function addUser($user_data, $role_id)
    {
        foreach($user_data as $key => $value) {
            $user_data[$key] = htmlspecialchars(studip_utf8encode($user_data[$key]));
        }

        $usr_xml = "<Users>
<User>
<UDFDefinitions></UDFDefinitions>
<Login>".$user_data["login"]."</Login>
<Password Type=\"PLAIN\">".$user_data["passwd"]."</Password>
<Firstname>".$user_data["firstname"]."</Firstname>
<Lastname>".$user_data["lastname"]."</Lastname>
<Title>".$user_data["title"]."</Title>
<Gender>".$user_data["gender"]."</Gender>
<Email>".$user_data["email"]."</Email>
<Street>".$user_data["street"]."</Street>
<PhoneHome>".$user_data["phone_home"]."</PhoneHome>
<Role Id=\"".$role_id."\" Type=\"Global\"/>
<Active>true</Active>
<TimeLimitUnlimited>".$user_data["time_limit_unlimited"]."</TimeLimitUnlimited>
<TimeLimitMessage>0</TimeLimitMessage>
<ApproveDate>".$user_data["approve_date"]."</ApproveDate>
<AgreeDate>".$user_data["agree_date"]."</AgreeDate>";
        if (($user_data["user_skin"] != "") OR ($user_data["user_style"] != "")) {
            $usr_xml .= "<Look Skin=\"".$user_data["user_skin"]."\" Style=\"".$user_data["user_style"]."\"/>";
        }
        $usr_xml .= "<AuthMode type=\"".$user_data["auth_mode"]."\"/>
<ExternalAccount>".$user_data["external_account"]."</ExternalAccount>
</User>
</Users>";

        $param = array(
            'sid' => $this->getSID(),
            'folder_id' => -1,
            'usr_xml' => $usr_xml,
            'conflict_role' => 1,
            'send_account_mail' => 0
        );
        $result = $this->call('importUsers', $param);

        $s = simplexml_load_string(studip_utf8encode($result));

        if ($s->rows->row->column[3] == "successful")
        return $s->rows->row->column[0];
        else
        return false;
    }

    /**
     * copy object
     *
     * copy ilias-object
     * @access public
     * @param string source_id reference-id
     * @param string target_id reference-id
     * @return string result
     */
    function copyObject($source_id, $target_id)
    {
        $type = $object_data["type"];
        $title = studip_utf8encode($object_data["title"]);
        $description = studip_utf8encode($object_data["description"]);

        $xml = "<Settings source_id=\"$source_id\" target_id=\"$target_id\" default_action=\"COPY\"/>";

        $param = array(
            'sid' => $this->getSID(),
            'xml' => $xml
        );
        return $this->call('copyObject', $param);
    }

    /**
     * get path
     *
     * returns repository-path to ilias-object
     * @access public
     * @param string source_id reference-id
     * @param string target_id reference-id
     * @return string result
     */
    function getPath($ref_id)
    {
        $param = array(
            'sid' => $this->getSID(),
            'ref_id' => $ref_id
        );
        $result = $this->call('getPathForRefId', $param);

        if ($result) {
            $s = simplexml_load_string(studip_utf8encode($result));

            foreach ($s->rows->row as $row) {
                $path[] = htmlReady(studip_utf8decode($row->column[2]));
            }
        }

        if (is_array($path)) {
            return implode($path, $this->seperator_string);
        } else {
            return false;
        }
    }

     /**
     *
     * returns repository-path to ilias-object
     *
     * @access public
     * @param string source_id reference-id
     * @param string target_id reference-id
     * @return string result
     */
    function getRawPath($ref_id)
    {
        $param = array(
            'sid' => $this->getSID(),
            'ref_id' => $ref_id
        );
        $result = $this->call('getPathForRefId', $param);

        if ($result) {
            $s = simplexml_load_string(studip_utf8encode($result));

            foreach ($s->rows->row as $row) {
                $path[] = (string)$row->column[0];
            }
        }

        if (is_array($path)) {
            return implode($path, '_');
        } else {
            return false;
        }
    }
}