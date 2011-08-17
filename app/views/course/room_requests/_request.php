<div style="padding:5px;border: 1px dotted">
<?php
echo '<h4>' . htmlReady($request->getTypeExplained()) . '</h4>';
echo '<div>'._("Anfragender:") . ' ' . htmlReady($request['user_id'] ? get_fullname($request['user_id']) : '') . '</div>';
echo '<div>'._("Letzte Änderung:") . ' ' . htmlReady(strftime('%x',$request['chdate'])) . '</div>';
echo htmlReady($request->getInfo(),1,1);
?>
</div>
