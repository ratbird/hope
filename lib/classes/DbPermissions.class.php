<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
// erstellt von Alex


class DbPermissions {
  function search_range($search_str) {
    global $perm, $auth, $_fullname_sql, $user;
      
    /* If user is root --------------------------------------------------- */
    if ($perm->have_perm("root")) {
      $query = 
	"SELECT ".
	" a.user_id, ". $_fullname_sql['full'] . 
	" AS full_name,username ".
	"FROM".
	" auth_user_md5 a LEFT JOIN user_info USING(user_id) ".
	"WHERE".
	" CONCAT(Vorname,' ',Nachname,' ',username) LIKE '%$search_str%'";
      $this->db->query($query);

      while($this->db->next_record()) {
	$this->search_result[get_username ($this->db->f ("user_id"))] = 
	  array ("type" => "user",
		 "name" => $this->db->f("full_name").
		 "(".$this->db->f("username").")");
      }
      $query = 
	"SELECT".
	" Seminar_id, Name ".
	"FROM".
	" seminare ".
	"WHERE".
	" Name LIKE '%$search_str%'";
      $this->db->query($query);
	 
      while($this->db->next_record()) {
	$this->search_result[$this->db->f("Seminar_id")] = 
	  array("type" => "sem",
		"name" => $this->db->f("Name"));
      }

      $query="SELECT Institut_id,Name, IF(Institut_id=fakultaets_id,'fak','inst') AS inst_type FROM Institute WHERE Name LIKE '%$search_str%'";
      $this->db->query($query);
      while($this->db->next_record()) {
	$this->search_result[$this->db->f("Institut_id")]=array("type"=>$this->db->f("inst_type"),"name"=>$this->db->f("Name"));
      }
    } 
    /* ------------------------------------------------------------------- */

    /* If user is an admin ----------------------------------------------- */
    elseif ($perm->have_perm("admin")) {
      $query = 
	"SELECT".
	" b.Seminar_id, b.Name ".
	"FROM".
	" user_inst AS a LEFT JOIN seminare AS b USING (Institut_id) ".
	"WHERE".
	" a.user_id = '".$user->id."' ".
	"  AND".
	" a.inst_perms = 'admin'".
	"  AND".
	" b.Name LIKE '%$search_str%'";
      $this->db->query($query);
	 
      while($this->db->next_record()) {
	$this->search_result[$this->db->f ("Seminar_id")] =
	  array ("type" => "sem",
		 "name" => $this->db->f ("Name"));
      }
      $query = 
	"SELECT".
	" b.Institut_id, b.Name ".
	"FROM".
	" user_inst AS a LEFT JOIN Institute AS b USING (Institut_id) ".
	"WHERE".
	" a.user_id= '".$user->id."'".
	"  AND".
	" a.inst_perms = 'admin'".
	"  AND".
	" a.institut_id != b.fakultaets_id".
	"  AND".
	" b.Name LIKE '%$search_str%'";
      $this->db->query($query);

      while($this->db->next_record()) {
	$this->search_result[$this->db->f("Institut_id")]=array("type"=>"inst","name"=>$this->db->f("Name"));
      }
      if ($perm->is_fak_admin()) {
	$query = "SELECT d.Seminar_id,d.Name FROM user_inst a LEFT JOIN Institute b ON(a.Institut_id=b.Institut_id AND b.Institut_id=b.fakultaets_id)  
				LEFT JOIN Institute c ON(c.fakultaets_id = b.institut_id AND c.fakultaets_id!=c.institut_id) LEFT JOIN seminare d USING(Institut_id) 
				WHERE a.user_id='".$user->id."' AND a.inst_perms='admin' AND NOT ISNULL(b.Institut_id) AND d.Name LIKE '%$search_str%'";
	$this->db->query($query);
	while($this->db->next_record()){
	  $this->search_result[$this->db->f("Seminar_id")]=array("type"=>"sem","name"=>$this->db->f("Name"));
	}
	$query = "SELECT c.Institut_id,c.Name FROM user_inst a LEFT JOIN Institute b ON(a.Institut_id=b.Institut_id AND b.Institut_id=b.fakultaets_id)  
				LEFT JOIN Institute c ON(c.fakultaets_id = b.institut_id AND c.fakultaets_id!=c.institut_id) 
				WHERE a.user_id='".$user->id."' AND a.inst_perms='admin' AND NOT ISNULL(b.Institut_id) AND c.Name LIKE '%$search_str%'";
	$this->db->query($query);
	while($this->db->next_record()){
	  $this->search_result[$this->db->f("Institut_id")]=array("type"=>"inst","name"=>$this->db->f("Name"));
	}
	$query = "SELECT b.Institut_id,b.Name FROM user_inst a LEFT JOIN Institute b ON(a.Institut_id=b.Institut_id AND b.Institut_id=b.fakultaets_id)  
				WHERE a.user_id='".$user->id."' AND a.inst_perms='admin' AND NOT ISNULL(b.Institut_id) AND b.Name LIKE '%$search_str%'";
	$this->db->query($query);
	while($this->db->next_record()){
	  $this->search_result[$this->db->f("Institut_id")]=array("type"=>"fak","name"=>$this->db->f("Name"));
	}
      }
			
    } 

    /* is tutor ------------------------------- */
      
    elseif ($perm->have_perm ("tutor")) {
      $query = 
	"SELECT".
	" b.Seminar_id, b.Name ".
	"FROM".
	" seminar_user AS a LEFT JOIN seminare AS b USING (Seminar_id) ".
	"WHERE".
	" a.user_id = '".$user->id."' AND a.status IN ('dozent', 'tutor')";
      $this->db->query($query);

      while($this->db->next_record()) {
	$this->search_result[$this->db->f ("Seminar_id")] = array("type" => "sem",
								  "name" => $this->db->f ("Name"));
      }
      $query = 
	"SELECT".
	" b.Institut_id, b.Name ".
	"FROM".
	" user_inst AS a LEFT JOIN Institute AS b USING (Institut_id) ".
	"WHERE".
	" a.user_id = '".$user->id."' AND a.inst_perms IN ('dozent', 'tutor')";
      $this->db->query($query);

      while($this->db->next_record()) {
	$this->search_result[$this->db->f("Institut_id")] = array("type" => "inst",
								  "name" => $this->db->f("Name"));
      }
      //$this->search_result[$this->user_id]=array("type"=>"user","name"=>$this->full_username."(".$auth->auth["uname"].")");
    }
    /* --------------------------------------- */

    /*
		[a] Was ger Kram hier soll weiß ich auch nicht

      if (is_array($this->search_result) && count($this->search_result)){
	 $query="SELECT range_id,COUNT(range_id) AS anzahl FROM news_range WHERE range_id IN ('".implode("','",array_keys($this->search_result))."') GROUP BY range_id";
	 $this->db->query($query);
	 while($this->db->next_record()) {
	    $this->search_result[$this->db->f("range_id")]["anzahl"]=$this->db->f("anzahl");
	 }
      }
    */
	
	 
    return $this->search_result;
  }
}
?>