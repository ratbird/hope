<?php
$search; //instance of SearchType ?
$searchresults; //array

$output = array();
foreach ($searchresults as $number => $result) {
	$res_array = array();
	$res_array['item_id'] = $result[0];
	$res_array['item_name'] = "";
	if ($this->search instanceof SearchType) {
		$res_array['item_name'] .= $this->search->getAvatarImageTag($result[0]);
	}
	if ($this->search == "username") {
		$res_array['item_name'] .= Avatar::getAvatar(get_userid($result[0]))->getImageTag(Avatar::SMALL);
	}
	if ($this->search == "user_id") {
		$res_array['item_name'] .= Avatar::getAvatar($result[0])->getImageTag(Avatar::SMALL);
	}
	if (($this->search == "Seminar_id") || ($this->search == "Arbeitsgruppe_id")) {
		$res_array['item_name'] .= CourseAvatar::getAvatar($result[0])->getImageTag(Avatar::SMALL);
	}
	if ($this->search == "Institut_id") {
		$res_array['item_name'] .= InstituteAvatar::getAvatar($result[0])->getImageTag(Avatar::SMALL);
	}
	if ($this->search == "special") {
		switch ($this->avatarLike) {
			case "username":
				$res_array['item_name'] .= Avatar::getAvatar(get_userid($result[0]))->getImageTag(Avatar::SMALL);
				break;
			case "user_id":
				$res_array['item_name'] .= Avatar::getAvatar($result[0])->getImageTag(Avatar::SMALL);
				break;
			case "Seminar_id":
				$res_array['item_name'] .= CourseAvatar::getAvatar($result[0])->getImageTag(Avatar::SMALL);
				break;
			case "Institut_id":
				$res_array['item_name'] .= InstituteAvatar::getAvatar($result[0])->getImageTag(Avatar::SMALL);
				break;
		}
	}
	$res_array['item_name'] .= $result[1];
	$output[] = $res_array;
}
print json_encode($output);