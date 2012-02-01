<?
# Lifter001: DONE
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO

// wikiMarkups are used by the wikiDirective function
// after all other conversions,
// wikiMarkup patterns are replaced
// args to wikiMarkup are passed to preg_replace
//
use Studip\Button, Studip\LinkButton;
require_once 'lib/forum.inc.php';

wikiMarkup('/\\(:biestform:\\)/e',"wiki_biestform('biest')", 'autor');
wikiMarkup('/\\(:biestlist\\s*(.*?):\\)/e',"wiki_biestlist('biest',array('q'=>'$1'))");

$biest_templates['biest']=array(
    // common prefix to alle newly created pages
    // must be a WikiWord and should be unique to
    // avoid conflicts with other templates
    "prefix"=>"BIEST", 
    // Some Text to display as form heading
    "formheading"=>"<h2>"._("Neuer BIEST-Eintrag")."</h2><p>"._("Name des Autoren und Erstellungszeit werden automatisch hinzugef&uuml;gt.")."</p>",
    // body of form for new entries, is embedded in <form>..</form>
    // environment. Make sure that field names match variable names
    // in template (see below)
    "formbody"=>"<table>
    <tr><td>Zusammenfassung:</td>
    <td><input size=60 name=\"biest_zusammenfassung\"></td></tr>
    <tr><td>Zuständig:</td>
    <td><input size=60 name=\"biest_zustaendig\"><br>
    <font size=\"-1\">Bitte den Nachnamen des zuständigen Programmierers eintragen, mehrere Personen k&ouml;nnen genannt werden</font><td></tr>
    <tr><td>Version:</td>
    <td><input size=60 name=\"biest_version\"></td></tr>
    <tr><td>Beschreibung:</td>
    <td><textarea name=\"biest_beschreibung\" cols=60 rows=10></textarea></td></tr>
    <tr><td>Foren-Thema erzeugen:</td>
    <td><input type=\"checkbox\" name=\"biest_create_topic\" value=\"1\" checked></td></tr>
    
    <tr><td>&nbsp;</td><td> '.Button::create(_('eintragen')). '</td></tr>
    </table>",
    // template is evaluated alter to form default text
    // important: make sure that variables evaluate at the right time
    // you may use predefined: 
    // - $author for author name
    // - $create_time for time at creation
    "template"=>'!!!!$pagename
Autor: $author
Erstellt: $create_time
Zuständig: $biest_zustaendig
Version: $biest_version
Zusammenfassung: $biest_zusammenfassung
Status: offen
Beschreibung: $biest_beschreibung', 
    // list of fields to parse for list view, matching is case-insensitive
    // order must be same as indicated by listheader
    // first field (name) will be added
    "listview"=>array('erstellt','autor','zuständig','version','status','zusammenfassung'),
    // standard order of fields for sort function
    "stdorder"=>'-erstellt,status,autor,zuständig,version,beschreibung',
    // header for list tables, first column always is the pages name
    // order defines order criterion for sort action
    "listheader"=>array(array("order"=>"-name","heading"=>"BIEST#"),
        array("order"=>"erstellt", "heading"=>"Erstellt"),
        array("order"=>"autor", "heading"=>"Autor"),
        array("order"=>"zuständig", "heading"=>"Zuständig"),
        array("order"=>"version", "heading"=>"Version"),
        array("order"=>"status", "heading"=>"Status"),
        array("order"=>"zusammenfassung", "heading"=>"Zusammenfassung"))
);

// ---------- end of config ---------------------------------------

if ($_REQUEST['biest_action']=='new_biest') {
    // add new biest-page to wiki pages
    wiki_newbiest($_REQUEST['biest_template']);
}

// create biest form
//
function wiki_biestform($template_name) {
    global $keyword;
    global $biest_templates;
    $template=$biest_templates[$template_name];
    if (!is_array($template)) { echo "<h1>Error: unknown template $template_name"; die(); }

    $form=$template['formheading'];
    $form.="<form action=\"".URLHelper::getLink('')."\" method=post>\n
        <input type=\"hidden\" name=\"biest_action\" value=\"new_biest\">
        <input type=\"hidden\" name=\"biest_template\" value=\"$template_name\">
        <input type=\"hidden\" name=\"keyword\" value=\"$keyword\">";
    $form.= CSRFProtection::tokenTag();
    $form.=$template['formbody'];
    $form.="</form>";
    return $form;
}

// get list of biest entries
//
function wiki_get_biestpagelist($template) {
    $query = "SELECT DISTINCT keyword FROM wiki WHERE range_id = ? AND keyword LIKE CONCAT(?, '%')";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($GLOBALS['SessSemName'][1], $template['prefix']));
    $list = $statement->fetchAll(PDO::FETCH_COLUMN);

    return $list;
}
 
// create new biest page
// data is passed from form defined in wiki_biestform()
//
function wiki_newbiest($template_name) {
    global $SessSemName, $auth;
    global $keyword, $view;
    global $biest_templates;
    $template=$biest_templates[$template_name];
    extract($_POST,EXTR_SKIP); // locally set post-vars for template
    $list=wiki_get_biestpagelist($template);
    foreach ($list as $l) {
        $issue=max(@$issue, substr($l,strlen($template['prefix'])));
    }
    $pagename=sprintf("%s%05d",$template['prefix'],@$issue+1);
    $create_time=date('Y-m-d H:i',time());
    $author=get_fullname(NULL,'no_title_short');
// print "<p>template ist: <pre>"; print_r($template); print "</pre>";
// print "<p>evaling: <pre>"."\$text=".$template['template'].";"."</pre>";
    eval("\$text=\"".$template['template']."\";");
// print "<p>Generierter Text:<br>$text"; // debug
    $userid=$auth->auth['uid'];

    $query = "INSERT INTO wiki (range_id, keyword, body, user_id, chdate, version)"
           . "VALUES (?, ?, ?, ?, UNIX_TIMESTAMP(), '1')";
    DBManager::get()
        ->prepare($query)
        ->execute(array($GLOBALS['SessSemName'][1], $pagename, $text, $userid));

    $message = MessageBox::success(sprintf(_('Ein neuer Eintrag wurde angelegt. Sie können ihn nun weiter bearbeiten oder %szurück zur Ausgangsseite%s gehen.'),'<a href="'.URLHelper::getLink("?keyword=$keyword").'">','</a>'));
    PageLayout::postMessage($message);
    if ($biest_create_topic){
        if(CreateTopic($pagename . ': ' . $biest_zusammenfassung, get_fullname($userid), $biest_beschreibung, 0, 0, $SessSemName[1],$userid)){
            $message = MessageBox::success(_('Ein neues Thema im Forum wurde angelegt.'));
            PageLayout::postMessage($message);
        }
    }
    $view='show';
    $keyword=$pagename;
    return;
}

// wiki_biestplist creates a table of biest issues according to various
// criteria.  
function wiki_biestlist($template_name,$opt) {
    global $SessSemName;
    global $keyword, $show_wiki_comments, $biest_templates;
    $template=$biest_templates[$template_name];
    $opt = array_merge($opt,(array)$_REQUEST);
    $biestlist = wiki_get_biestpagelist($template);
    $out[] = "<table border='1' cellspacing='0' cellpadding='3'></tr>";
    foreach ($template['listheader'] as $h) {
        $out[]="<th><a href='".URLHelper::getLink("?keyword=$keyword&order=".urlencode($h['order']))."'>{$h['heading']}</a></th>";
    }
    $out[]="</tr>\n";
    $terms = preg_split('/((?<!\\S)[-+]?[\'"].*?[\'"](?!\\S)|\\S+)/',
        $opt['q'],-1,PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
    foreach($terms as $t) {
        if (trim($t)=='') continue;
        if (preg_match('/([^\'":=]*)[:=]([\'"]?)(.*?)\\2$/',$t,$match))
            $opt[strtolower($match[1])] = $match[3];
    }
    $n=0; $slist=array();
    foreach($biestlist as $s) {
        $page = getLatestVersion($s,$SessSemName[1]);
        preg_match_all("/(^|\n)([A-Za-z][^:]*):([^\n]*)/",$page['body'],$match);
        $fields = array();
        for($i=0;$i<count($match[2]);$i++)
            $fields[strtolower($match[2][$i])] = htmlentities($match[3][$i],ENT_QUOTES);
        foreach(explode(',',$template['stdorder']) as $h) {
            $h_html = htmlentities($h);
             if (!@$opt[$h_html]) continue;
             foreach(preg_split('/[ ,]/',$opt[$h_html]) as $t) {
                if (substr($t,0,1)!='-' && substr($t,0,1)!='!') {
                    if (strpos(strtolower(@$fields[$h]),strtolower($t)) === false)
                        continue 3;
                } else if (strpos(strtolower(@$fields[$h]), strtolower(substr($t,1)))!==false)
                    continue 3;
            }
        }
        $slist[$n] = $fields;
        $slist[$n]['name'] = $s;
        $n++;
    }
    $cmp = CreateOrderFunction(@$opt['order'].",".$template['stdorder']);
    usort($slist,$cmp);
    foreach($slist as $s) {
        $out[] = "<tr><td><font size=-1><a href='".URLHelper::getLink("?keyword=$s[name]")."'>$s[name]</a></font></td>";
        foreach($template['listview'] as $h) 
            $out[] = @"<td><font size=-1>".wikiLinks(wikiReady(decodeHTML($s[$h]),TRUE,FALSE,$show_wiki_comments), $keyword)."&nbsp;</font></td>";
            $out[] = "</tr>";
    }
    $out[] = "</table>";
    return implode('',$out);
}

// This function creates specialized ordering functions needed to
// (more efficiently) perform sorts on arbitrary sets of criteria.
/*function CreateOrderFunction($order) { 
  $code = '';
  foreach(preg_split('/[\\s,|]+/',strtolower($order),-1,PREG_SPLIT_NO_EMPTY) 
      as $o) {
    if (substr($o,0,1)=='-') { $r='-'; $o=substr($o,1); }
    else $r='';
    if (preg_match('/\\W/',$o)) continue;
    $code .= "\$c=strcasecmp(@\$x['$o'],@\$y['$o']); if (\$c!=0) return $r\$c;\n";
  }
  $code .= "return 0;\n";
  return create_function('$x,$y',$code);
}*/


?>
