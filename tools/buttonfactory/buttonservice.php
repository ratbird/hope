<?
require_once('button.class.php');

$available_buttons = array('button','buttonno','buttoncheck','buttoncreate1','buttoncreate2');
$font = 'arialuni.ttf';

$args = array();
if(strlen($_SERVER['PATH_INFO'])){
	$args = explode('/',$_SERVER['PATH_INFO']);
	array_shift($args);
}
if(count($args)){
	$name = trim($args[0]);
	if(in_array(trim($args[1]), $available_buttons)) $button = trim($args[1]);
	else $button = $available_buttons[0];
	$korrektur = (int)$args[2];
	$foo = new button('', $name, '', $font, $button . '.png', $korrektur);
    header('Content-type: image/png');
	$foo->RenderButton(true);
}
?>