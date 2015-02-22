<?php
$seminar = get_object_name($topic['seminar_id'], 'sem');
array_pop($path); // last element is the entry itself

$message = array(
   'header' => sprintf(
        _('Im Forum der Veranstaltung **%s** gibt es einen neuen Beitrag unter **%s** von **%s**'),
        $seminar['name'],
        implode(' > ', array_map(function ($p) { return $p['name']; }, $path)),
        $topic['anonymous'] ? _('Anonym') : $topic['author']
    ),
    'title' => $topic['name'] ? '**' . $topic['name'] ."** \n\n" : '',
    'content' => $topic['content'],
    'url' => '<a href="' . UrlHelper::getUrl(
        $GLOBALS['ABSOLUTE_URI_STUDIP']
        . 'plugins.php/coreforum/index/index/'
        . $topic['topic_id']
        .'?cid='
        . $topic['seminar_id'] 
        .'&again=yes#' 
        . $topic['topic_id']
    ) . '">Beitrag im Forum ansehen.</a>'
);

// since we've possibly got a mixup of HTML and Stud.IP markup,
// create a pure HTML message step by step
$htmlMessage = '<div>'
    . implode('<br><br>', array_map('formatReady', $message))
    . '</div>';

echo $htmlMessage;

