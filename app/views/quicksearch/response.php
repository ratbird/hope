<?php
# Lifter010: TODO
$search; //instance of SearchType ?
$searchresults; //array

$output = array();
foreach ($searchresults as $number => $result) {
    $res_array = array();
    $res_array['item_id'] = $result[0];
    $res_array['item_name'] = "";
    if ($search instanceof StandardSearch && $search->extendedLayout) {
        $res_array['item_name'] .= $search->getAvatarImageTag(Avatar::MEDIUM);
        $res_array['item_description'] = $result[2] . " (" . $result[3] . ")";
    } else if ($search instanceof SearchType) {
        $res_array['item_name'] .= $search->getAvatarImageTag($result[0]);
    }
    $res_array['item_name'] .= $result[1];
    $res_array['item_search_name'] = $result[count($result)-1];
    $output[] = $res_array;
}
print json_encode(studip_utf8encode($output));
