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

wikiMarkup('/\\(:liftersform:\\)/e',"wiki_liftersform('lifters')", 'dozent');
wikiMarkup('/\\(:lifterslist\\s*(.*?):\\)/e',"wiki_lifterslist('lifters',array('q'=>'$1'))");
wikiMarkup('/\\(:liftersprogress\\s*(.*?):\\)/e',"wiki_liftersprogress('lifters','$1')");

$lifters_templates['lifters'] = array(
    // common prefix to alle newly created pages
    // must be a WikiWord and should be unique to
    // avoid conflicts with other templates
    'prefix' => 'LifTers',
    // Some Text to display as form heading
    'formheading' => '<h2>'._("Neuer Lifters-Eintrag").'</h2><p>'._("Name des Autoren und Erstellungszeit werden automatisch hinzugefügt.").'</p>',
    // body of form for new entries, is embedded in <form>..</form>
    // environment. Make sure that field names match variable names
    // in template (see below)
    'formbody' => '<table>
<tr><td>Zusammenfassung:</td>
<td><input size=60 name="lifters_zusammenfassung"></td></tr>
<tr><td>Zuständig:</td>
<td><input size=60 name="lifters_zustaendig"></td></tr>
<tr><td>Release in Version:</td>
<td><select size=0 name="lifters_version">
<option value="2.1">2.1 (März 2011)</option>
<option value="2.2">2.2 (Sept. 2011)</option>
<option>langfristig</option></td></tr>
<tr><td>Komplexität:</td>
<td><select name="lifters_komplexitaet">
<option>gering</option>
<option>mittel</option>
<option>hoch</option></select></td></tr>
<tr><td>Beschreibung:</td>
<td><textarea name="lifters_beschreibung" cols="60" rows="10"></textarea></td></tr>
<tr><td>Foren-Thema erzeugen:</td>
<td><input type="checkbox" name="lifters_create_topic" value="1" checked></td></tr>

<tr><td>&nbsp;</td><td> '.Button::create(_('Eintragen')). '</td></tr>
</table>',
    // template is evaluated alter to form default text
    // important: make sure that variables evaluate at the right time
    // you may use predefined:
    // - $author for author name
    // - $create_time for time at creation
    'template' => '!!!!$pagename
Zusammenfassung: $lifters_zusammenfassung
Autor: $author
Version: $lifters_version
Zuständig: $lifters_zustaendig
Komplexität: $lifters_komplexitaet
Erstellt: $create_time
Status: neu
Beschreibung:

$lifters_beschreibung

(:liftersprogress:)
',
    // list of fields to parse for list view, matching is case-insensitive
    // order must be same as indicated by listheader
    // first field (name) will be added
    "listview"=>array('erstellt','autor','zuständig','version','komplexität','status','zusammenfassung'),
    // standard order of fields for sort function
    "stdorder"=>'-erstellt,status,version,autor,zuständig,zusammenfassung',
    // header for list tables, first column always is the pages name
    // order defines order criterion for sort action
    "listheader"=>array(array("order"=>"-name","heading"=>"Lifters#"),
        array("order"=>"erstellt", "heading"=>"Erstellt"),
        array("order"=>"autor", "heading"=>"Autor"),
        array("order"=>"zuständig", "heading"=>"Zuständig"),
        array("order"=>"version", "heading"=>"Version"),
        array("order"=>"komplexität", "heading"=>"Komplex."),
        array("order"=>"status", "heading"=>"Status"),
        array("order"=>"zusammenfassung", "heading"=>"Zusammenfassung"))
);

// ---------- end of config ---------------------------------------

if ($_REQUEST['lifters_action']=='new_lifters') {
    // add new Lifters-page to wiki pages
    wiki_newlifters($_REQUEST['lifters_template']);
}

// create Lifters form
//
function wiki_liftersform($template_name) {
    global $keyword;
    global $lifters_templates;
    $template=$lifters_templates[$template_name];
    if (!is_array($template)) { echo "<h1>Error: unknown template $template_name"; die(); }

    $form=$template['formheading'];
    $form.="<form action=\"".URLHelper::getLink('')."\" method=post>\n
        <input type=\"hidden\" name=\"lifters_action\" value=\"new_lifters\">
        <input type=\"hidden\" name=\"lifters_template\" value=\"$template_name\">
        <input type=\"hidden\" name=\"keyword\" value=\"$keyword\">";
    $form.= CSRFProtection::tokenTag();
    $form.=$template['formbody'];
    $form.="</form>";
    return $form;
}

// get list of Lifters entries
//
function wiki_get_lifterspagelist($template) {
    $query = "SELECT DISTINCT keyword FROM wiki WHERE range_id = ? AND keyword LIKE CONCAT(?, '%')";
    $statement = DBManager::get()->prepare($query);
    $statement->execute(array($GLOBALS['SessSemName'][1], $template['prefix']));
    $list = $statement->fetchAll(PDO::FETCH_COLUMN);

    return $list;
}

// create new Lifters page
// data is passed from form defined in wiki_liftersform()
//
function wiki_newlifters($template_name) {
    global $SessSemName, $auth;
    global $keyword, $view;
    global $lifters_templates;
    $template = $lifters_templates[$template_name];
    extract($_POST,EXTR_SKIP); // locally set post-vars for template
    $list = wiki_get_lifterspagelist($template);
    foreach ($list as $l) {
        $issue=max(@$issue, substr($l,strlen($template['prefix'])));
    }
    $pagename = sprintf("%s%03d", $template['prefix'], @$issue+1);
    $create_time = date('Y-m-d H:i',time());
    $author = get_fullname(NULL, 'no_title_short');
// print "<p>template ist: <pre>"; print_r($template); print "</pre>";
// print "<p>evaling: <pre>"."\$text=".$template['template'].";"."</pre>";
    eval('$text="'. $template['template']. '";');
// print "<p>Generierter Text:<br>$text"; // debug
    $userid=$auth->auth['uid'];
    $wiki_text = $text;
    if ($lifters_create_topic){
        $forum_text = sprintf(_("Die aktuellste Fassung dieses Lifters finden Sie immer im %sWiki%s"),'[',']'.$GLOBALS['ABSOLUTE_URI_STUDIP'].'wiki.php?keyword='.$pagename) . " \n--\n". $lifters_beschreibung;
        if($tt = CreateTopic($pagename . ': ' . $lifters_zusammenfassung, get_fullname($userid), $forum_text, 0, 0, $SessSemName[1],$userid)) {
            $message = MessageBox::success(_('Ein neues Thema im Forum wurde angelegt.'));
            PageLayout::postMessage($message);
            $wiki_text = '['._("Link zum Forumsbeitrag").']' . $GLOBALS['ABSOLUTE_URI_STUDIP'] . 'forum.php?open=' . $tt . '#anker ' . "\n--\n" . $wiki_text;
        }
    }

    $query = "INSERT INTO wiki (range_id, keyword, body, user_id, chdate, version)"
           . "VALUES (?, ?, ?, ?, UNIX_TIMESTAMP(), '1')";
    DBManager::get()
        ->prepare($query)
        ->execute(array($GLOBALS['SessSemName'][1], $pagename, $wiki_text, $userid));

    $message = MessageBox::success(sprintf(_('Ein neuer Eintrag wurde angelegt. Sie können ihn nun weiter bearbeiten oder %szurück zur Ausgangsseite%s gehen.'),'<a href="'.URLHelper::getLink('?keyword='.$keyword).'">','</a>'));
    PageLayout::postMessage($message);
    $view = 'show';
    $keyword = $pagename;
    return;
}

// wiki_lifterslist creates a table of Lifters issues according to various
// criteria.
function wiki_lifterslist($template_name,$opt) {
    global $SessSemName;
    global $keyword, $show_wiki_comments, $lifters_templates;
    $template=$lifters_templates[$template_name];
    $opt = array_merge((array)$opt,(array)$_REQUEST);
    $lifterslist = wiki_get_lifterspagelist($template);
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
    foreach($lifterslist as $s) {
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
                    if (strpos(strtolower(@$fields[$h]),strtolower($t))===false)
                        continue 3;
                } else if (strpos(strtolower(@$fields[$h]), strtolower(substr($t,1)))!==false)
                    continue 3;
            }
        }
        $slist[$n] = $fields;
        $slist[$n]['name'] = $s;
        $n++;
    }
    $cmp = wiki_lifters_CreateOrderFunction(@$opt['order'].",".$template['stdorder']);
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
function wiki_lifters_CreateOrderFunction($order) {
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
}

// This function shows the progress of a lister.
function wiki_liftersprogress($template_name, $lnr){

    global $lifters_templates;

    # retrieve ID of lifters from keyword of wiki page
    if ($lnr === '' && isset($_GET['keyword'])) {
        if (preg_match('/^' . $lifters_templates[$template_name]['prefix'] .
                       '([0-9])+$/', $_GET['keyword'], $matches)) {
            $lnr = $matches[1];
        }
    }
    $id = (int)$lnr;

    if (!$id) {
        return '';
    }

    $cache_key = "wiki/liftersprogress/" . $id;
    $cache = StudipCacheFactory::getCache();
    $result = $cache->read($cache_key);

    if ($result === FALSE) {
        $command = sprintf('cd %s ; tools/lifter/lifter-status -l%d',
            $GLOBALS['STUDIP_BASE_PATH'], $id);
        exec($command, $output, $return_var);

        $out = array();
        if (!$return_var) {
            $out[] = '<h1>'._("Status von Lifters") . sprintf('%03d', $id) . '</h1>';
            $out[] = '<p>' . strftime("%x %X", time()) . '</p>';

            if (count($output)) {
                $out[] =  '<h3>' . htmlReady(array_pop($output)) .'</h3>';
                $out[] = '<pre>';
                foreach($output as $line) {
                    $out[] = htmlReady($line);
                }
                $out[] = '</pre>';
            }
        }

        $result = implode("\n", $out);
        $cache->write($cache_key, $result, 300);
    }
    return $result;
}
