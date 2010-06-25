<?php
$search; //instance of SearchType ?
$searchresults; //array

$output = array();
foreach ($searchresults as $number => $result) {
    $res_array = array();
    $res_array['item_id'] = $result[0];
    $res_array['item_name'] = "";
    if ($search instanceof SearchType) {
        $res_array['item_name'] .= $search->getAvatarImageTag($result[0]);
    }
    $res_array['item_name'] .= utf8_encode($result[1]);
    $output[] = $res_array;
}
print json_encode($output);