<?
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
class Debug {

	function debug($text, $dl = 0) {
		$debug_level = 1;		// 0 = no debug_messages, 9 = all debug_messages
		if ($debug_level <= $dl) return FALSE;
		echo "<pre>$text\n</pre>";		
	}
	
	function debug_r($arr, $dl = 0) {
		$debug_level = 1;		// 0 = no debug_messages, 9 = all debug_messages
		if ($debug_level <= $dl) return FALSE;
		echo '<pre>'.print_r($arr, TRUE).'</pre>';
	}

}
?>
