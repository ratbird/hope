<?php
# Lifter007: TODO
# Lifter003: TODO

require_once 'lib/functions.php';
require_once 'app/models/content_element.php';
require_once 'app/controllers/authenticated_controller.php';

class ContentElementController extends AuthenticatedController {


  function before_filter(&$action, &$args) {
	parent::before_filter($action, $args);

	list($type, $id) = $args;
	$content_class = 'StudipContentElement' . $type;
	if($type && class_exists($content_class)){
		$this->element = new $content_class($id);
		if(!$this->element->isAccessible($GLOBALS['user']->id)){
			$this->set_status(401);
			$this->render_nothing();
			return false;
		}
	} else {
		$this->set_status(500);
		$this->render_nothing();
		return false;
	}
  }

  function get_formatted_action(){
	  return $this->render_json(array(
		  							'title' => studip_utf8encode(htmlspecialchars($this->element->getTitle())),
		  							'content' => studip_utf8encode($this->element->getAbstractHtml())
									)
		  						);
  }
  
  function get_raw_action(){
	  return $this->render_json(array(
		  							'title' => studip_utf8encode($this->element->getTitle()),
		  							'content' => studip_utf8encode($this->element->getAbstract())
									)
		  						);
  }
  
  function render_json($data){
	  $this->set_content_type('application/json;charset=utf-8');
	  return $this->render_text(json_encode($data));
  }
}
