<?php
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", user => "Seminar_User"));
$perm->check("root");
include "functions.php";
include "statusgruppe.inc.php";
set_time_limit(0); //bis zum bitteren Ende...
class FakeUser{
    var $user_variables;
    var $user_id = "nobody";
    var $name = "Seminar_User";
    var $session;

    function FakeUser(){
        $this->session = $GLOBALS['sess'];
        if (!is_object($this->session)){
            die("No Session Object found");
        }
    }

    function microwaveIt(){
        $this->user_variables = null;
        $this->session->get_lock();
        $vals = $this->session->that->ac_get_value($this->user_id, $this->name);
        $vals = str_replace("\$GLOBALS", "\$this->user_variables", $vals);
        eval(sprintf(";%s",$vals));
    }

    function &getVariable($name){
        if(!isset($this->user_variables[$name]))
            return false;
        return $this->user_variables[$name];
    }

    function killVariable($name){
        if(!isset($this->pt[$name]))
            return false;
        unset($this->pt[$name]);
        unset($this->user_variables[$name]);
        return true;
    }

    function freezeIt() {
        $str = "";
        $this->serialize("this->in",&$str);
        $this->serialize("this->pt",&$str);

        reset($this->pt);
        while ( list($thing) = each($this->pt) ) {
            $thing = trim($thing);
            if ($thing) {
                $this->serialize("this->user_variables['".$thing."']",&$str);
            }
        }
        $str = str_replace("\$this->user_variables", "\$GLOBALS", $str);
        $ret = $this->session->that->ac_store($this->user_id, $this->name, $str);
        $this->session->release_lock();

    }
    function serialize($prefix, $str) {
        static $t,$l,$k;

    ## Determine the type of $$prefix
    eval("\$t = gettype(\$$prefix);");
    switch ( $t ) {

    case "array":
        ## $$prefix is an array. Enumerate the elements and serialize them.
        eval("reset(\$$prefix); \$l = gettype(list(\$k)=each(\$$prefix));");
        $str .= "\$$prefix = array(); ";
        while ( "array" == $l ) {
            ## Structural recursion
            $this->serialize($prefix."['".ereg_replace("([\\'])", "\\\\1", $k)."']", &$str);
            eval("\$l = gettype(list(\$k)=each(\$$prefix));");
        }

    break;
    case "object":
        ## $$prefix is an object. Enumerate the slots and serialize them.
        eval("\$k = \$${prefix}->classname; \$l = reset(\$${prefix}->persistent_slots);");
        $str.="\$$prefix = new $k; ";
        while ( $l ) {
            ## Structural recursion.
            $this->serialize($prefix."->".$l,&$str);
            eval("\$l = next(\$${prefix}->persistent_slots);");
        }

    break;
    default:
        ## $$prefix is an atom. Extract it to $l, then generate code.
        eval("\$l = \$$prefix;");
        $str.="\$$prefix = '".ereg_replace("([\\'])", "\\\\1", $l)."'; ";


    break;
    }
    }
}

$db=new DB_Seminar;
$db2=new DB_Seminar;
$test = new FakeUser();
$c = 0;
$db->query("select * from active_sessions WHERE name = 'Seminar_User' AND sid != 'nobody'");
while ($db->next_record()) {
    $changed = $db->f("changed");
    $test->user_id = $db->f("sid");
    $test->microwaveIt();
    $my_array =& $test->getVariable("my_messaging_settings");
    echo ++$i . " : " . $db->f("sid") . " ";
    if (is_array($my_array)) {
        if ($my_array["sms_sig"])
            $my_array["addsignature"]=1;
        
        $test->freezeIt();
        $db2->query("UPDATE active_sessions set changed='$changed' WHERE name='Seminar_user' AND sid='".$db->f("sid")."'");
        if ($db2->affected_rows()) {
            echo "<br>Aenderungen &uuml;bernommen.";
        }
    } else {
        echo "Keine &Auml;nderungen vorgenommen.";
    }
    echo "<hr>";
}
echo "uff, geschafft!";

page_close();
?>
