<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1999 NetUSE GmbH
 *                    Kristian Koehntopp
 *
 *
 */ 
class Menu {
  var $classname = "Menu";

  # Map of PHP_SELF URL strings to menu positions
  var $urlmap = array();
  
  # current menu position
  var $map = "";

  # Information about each menu item
  var $item = array();

  # $children["/"] = array("/1", "/2");
  var $children = array();
  
  # $visible[1] = "/1";
  var $visible = array();

  # Class to render menu items in
  var $class = "";

  # Set true if you do not want to see the main menu
  var $nomain  = false;

  # Delimiter to separate components of the page title
  var $title_delim = " : ";

  /***************************************************************************/
  /* public: constructor
   */
  function Menu() {
    $this->setup();
  }
  
  /* public: show current menu
   */
   
  function show() {
    print $this->get();
  }
  
  function get() {
    
    # Determine normalized current position in tree
    $this->map = $this->normalize_pos($_SERVER['PHP_SELF']);

    # Determine menu levels up from current position
    $r = $this->split_path($this->map);

    # set up the visible menu items
    $this->find_visible($r);
    
    # create them
    $str = "";
    
    $start    = $this->nomain?1:0;
    $len      = count($this->visible);
    $oldlevel = 0;
    $str     .= $this->start_menu();
    for($i=$start; $i<$len; $i++) {
      $level = count(explode("/", $this->visible[$i]));

      if ($level > $oldlevel)
        $str .= $this->shift_in($oldlevel, $level);
      if ($level < $oldlevel)
        $str .= $this->shift_out($oldlevel, $level);
      $oldlevel = $level;

      $str .= $this->get_cell($i, $level, $this->class);
    }
    $str .= $this->end_menu();
    
    return $str;
  }
  
  /* public: show a single menu cell
   */
  function get_cell($n, $level, $class = "") {
    $bold = ($this->visible[$n] == $this->map);
    for ($i=1; $i<$level; $i++)
      $dent .= "&nbsp;&nbsp;";

    if ($this->item[$this->visible[$n]]["pseudo"]) {
      $str=sprintf("%s%s<br>\n",
           $dent,
           $this->item[$this->visible[$n]]["title"]
      );
    } else {
      $str = sprintf("%s<a href=\"%s\"%s>%s%s%s</a><br>\n", 
        $dent,
        $this->item[$this->visible[$n]]["url"],
        $class?" class=$class":"",
        $bold? '<B><FONT COLOR="#990000">[':"",
        $this->item[$this->visible[$n]]["title"],
        $bold? "]</FONT></B>":""
      );
    }
    return $str;
  }

  /* public: build the title of the page based on
             its location in the menu hierarchy. */
  function get_title() {
    unset($this->title);
    
    # Determine normalized current position in tree
    $this->map = $this->normalize_pos($_SERVER['PHP_SELF']);

    # Determine menu levels up from current position
    $r = $this->split_path($this->map);

    while(list($a, $b) = each($r)) {
      if ($this->title)
        $this->title .= $this->title_delim;
      $this->title .= $this->item[$b]["title"];
    }

    return $this->title;
  }

  function shift_in($oldlevel, $level) { ; }
  
  function shift_out($oldlevel, $level) { ; }

  function start_menu() { ; }

  function end_menu() { ; }

  /***************************************************************************/  
  /* private: normalize current menu position
   */
  function normalize_pos($pos) {
    if ( $m = $this->urlmap[basename($pos)] )
        return($m);
    $m = $pos;
    while(substr($m, 0, 1)) {
      if ($this->urlmap[$m])
        break;
      $m = dirname($m);
    }
    return $this->urlmap[$m];  
  }
  
  /* private: split a path /2/2 into components "", /2, /2/2
   */
  function split_path($p) {
    $path = "";

    $r    = explode("/", $p);
    reset($r);
    while(list($k, $v) = each($r)) {
      if ($v)
        $path .= "/$v";
      $res[] = $path;
    }
    
    return $res;
  }

  /* private: set up the visible array.
   */
  function find_visible($r) {
    # at each level, add current children to visible
    $len          = count($r);
    $this->visible= array();


    for ($i=0; $i<$len; $i++) {
      # if current level has children, add them...
      if (is_array($this->children[$r[$i]]) ) {
        reset($this->children[$r[$i]]);
        while(list($k, $v) = each($this->children[$r[$i]])) {
          $this->visible[] = $v;
          if (isset($this->item[$v][pseudo]) &&
              !preg_match("/^$v/",$this->map))
            while (list(,$w) = each($this->item[$v][pseudo])) {
              $this->visible[]=$w;
            }
        }
      }
    }
    
    # Order menu items for display
    sort($this->visible);
  }

  /* private: find children of each menu item
   */
  function setup() {
    reset($this->urlmap);
    while(list($k, $v) = each($this->urlmap)) {
      $base = dirname($v);
      $this->children[$base][] = $v;
      $this->item[$v]["url"]   = $k;
    }
  }
}
?>
