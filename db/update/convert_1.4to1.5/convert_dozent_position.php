<?
page_open(array("sess" => "Seminar_Session", "auth" => "Seminar_Auth", "perm" => "Seminar_Perm", "user" => "Seminar_User"));
$perm->check("root");
function resort($status){
	$db = new DB_Seminar();
	$result = new DB_Seminar("SELECT su.position, su.status, su.Seminar_id, " .
						" su.user_id, Nachname " .
						" FROM seminar_user su " .
						"  LEFT JOIN auth_user_md5 USING(user_id) " . 
						" WHERE status = '$status' " .
						" ORDER BY Seminar_id, position, Nachname ");
	$old_sid  = 0;
	$position = 0;       
	
	while($result->next_record())
	{
		$cur_sid = $result->f("Seminar_id");
		
		if ($cur_sid != $old_sid)
		{
			echo "\n";
			$position = 0;       
			$old_sid = $cur_sid; 
		}
		echo "UPDATE: " . $result->f("Seminar_id") . 
		" Old: " . $result->f("position") . 
		" New: "  . $position . " " . 
		$result->f("Nachname") . "\n";
		
		
		$db->query("UPDATE seminar_user SET position = '". $position . "' " .
					" WHERE Seminar_id = '" . $result->f("Seminar_id") . "' " .
					"  AND  user_id    = '" . $result->f("user_id") . "' ");
		
		$position++;
	}
}
echo "<pre>";
echo "Sortierung Dozenten:\n";
resort('dozent');
echo "<hr>";
echo "Sortierung Tutoren:\n";
resort('tutor');
?>
