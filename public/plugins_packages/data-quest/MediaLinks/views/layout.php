<?
include 'lib/include/html_head.inc.php';
include 'lib/include/header.php';

?>
<div id="layout_container" style="padding-top:1px;">
<?
if($question) echo $question;

if (is_array($msg = $flash->get('msg'))) {
    foreach($msg as $one_msg) {
        list($type, $content, $aux) = $one_msg;
        echo call_user_func(array('Messagebox',$type), $content, $aux);
    }
}
if($infobox){
	if(!$infobox['template']){
		$infobox['template'] = $standard_templates . 'infobox/infobox_generic_content';
	}
?>
<div id="layout_infobox">
	<?= $this->render_partial($infobox['template'], $infobox) ?>
</div>
<div id="layout_content">
	<?= $content_for_layout ?>
</div>
<div class="clear"></div>
<?
} else {
	echo $content_for_layout;
}
?>

</div>

<? include 'lib/include/html_end.inc.php';
