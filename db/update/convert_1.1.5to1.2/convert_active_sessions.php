<?php
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "user" => "Seminar_user" , "perm" => "Seminar_Perm"));
$perm->check("root");

set_time_limit(0); //bis zum bitteren Ende...

class ConvertSeminarUser{
	var $user_variables;
	var $user_id = "nobody";
	var $name = "Seminar_User";
	var $session;
	
	function ConvertSeminarUser(){
	}
	
	function getValue(){
		$db = new DB_Seminar("SELECT val, unix_timestamp(changed) as last_changed from active_sessions WHERE name='{$this->name}' AND sid='{$this->user_id}'");
		$db->next_record();
		$this->last_changed = $db->f('last_changed');
		$data = base64_decode($db->f(0));
		if (preg_match("/^{$this->name}:/", $data)){
			return preg_replace("/^{$this->name}:/",'', $data, 1);
		} else {
			return false;
		}
	}
	
	
	function microwaveIt(){
		$this->user_variables = null;
		if ($vals = $this->getValue()){
			$vals = str_replace("\$GLOBALS", "\$this->user_variables", $vals);
			eval(sprintf(";%s",$vals));
		}
	}

	function freezeIt(){
		if ($this->pt && ($this->user_id != $GLOBALS['auth']->auth['uid'])){
			$fake_user = new Seminar_User($this->user_id);
			foreach ($this->pt as $thing => $value){
				$thing = trim($thing);
				if ($thing && $value){
					$fake_user->user_vars[$thing] = $this->user_variables[$thing];
				}
			}
			$r = $fake_user->freeze();
			$fake_user->set_last_action($this->last_changed);
			return $r;
		} else {
			return false;
		}
	}
}

echo "<pre>";
$converter = new ConvertSeminarUser();
$db = new DB_Seminar();
$db->query("SELECT sid,username FROM active_sessions INNER JOIN auth_user_md5 ON(sid=user_id) WHERE name='Seminar_User'");
while ($db->next_record()){
	echo $db->f('sid') . " : " . $db->f('username') . " ";
	$converter->user_id = $db->f('sid');
	$converter->microwaveIt();
	echo ($converter->freezeIt() ? "umgewandelt" : "nicht umgewandelt");
	echo "\n";
	flush();
}
echo "Optimiere Tabelle: " . PHPLIB_USERDATA_TABLE . "\n";
$db->query("OPTIMIZE TABLE " . PHPLIB_USERDATA_TABLE);
echo "uff, geschafft!";
page_close();
?>
