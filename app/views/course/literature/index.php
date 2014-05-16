<?php
if ($list){
    echo $list;
} else {
    echo _("Es wurde noch keine Literatur erfasst");
}
?>
<?php

$sidebar = Sidebar::get();
$sidebar->setImage(Assets::image_path("sidebar/literature-sidebar.png"));
