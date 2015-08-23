<?php
require_once 'vendor/HTMLPurifier/HTMLPurifier.auto.php';
require_once 'lib/visual.inc.php'; // import isLinkIntern

/**
 * Classify links as internal or external and set the class attribute 
 * accordingly.
 */
class HTMLPurifier_Injector_ClassifyLinks extends HTMLPurifier_Injector
{
    public $name = 'ClassifyLinks';
    public $needed = array('a' => array('href', 'class'));

    public function handleElement(&$token)
    {
        if ($token->name === 'a' && isset($token->attr['href'])) {
            $token->attr['class'] = isLinkIntern($token->attr['href']) ?
                'link-intern' : 'link-extern';
        }
    }
}
