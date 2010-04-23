<ul>
<?php
$search; //instance of SearchType ?
$searchresults; //array

foreach ($searchresults as $result) {
	print "<li id=\"".$result[0]."\">";
	if ($this->search instanceof SearchType) {
		print $this->search->getAvatarImageTag($result[0]);
	}
	if ($this->search == "username") {
		print Avatar::getAvatar(get_userid($result[0]))->getImageTag(Avatar::SMALL);
	}
	if ($this->search == "user_id") {
		print Avatar::getAvatar($result[0])->getImageTag(Avatar::SMALL);
	}
	if (($this->search == "Seminar_id") || ($this->search == "Arbeitsgruppe_id")) {
		print CourseAvatar::getAvatar($result[0])->getImageTag(Avatar::SMALL);
	}
	if ($this->search == "Institut_id") {
		print InstituteAvatar::getAvatar($result[0])->getImageTag(Avatar::SMALL);
	}
	if ($this->search == "special") {
		switch ($this->avatarLike) {
			case "username":
				print Avatar::getAvatar(get_userid($result[0]))->getImageTag(Avatar::SMALL);
				break;
			case "user_id":
				print Avatar::getAvatar($result[0])->getImageTag(Avatar::SMALL);
				break;
			case "Seminar_id":
				print CourseAvatar::getAvatar($result[0])->getImageTag(Avatar::SMALL);
				break;
			case "Institut_id":
				print InstituteAvatar::getAvatar($result[0])->getImageTag(Avatar::SMALL);
				break;
		}
	}
	print $result[1]."</li>";
}
?>
</ul>