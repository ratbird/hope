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
    public $needed = array('a' => ['href', 'class']);

    private $sanitizer;
    private $config;
    private $context;

    public function prepare($config, $context)
    {
        $this->sanitizer = new HTMLPurifier_AttrDef_URI();
        $this->config = $config;
        $this->context = $context;
        return parent::prepare($config, $context);
    }

    public function handleElement(&$token)
    {
        if ($token->name !== 'a' || !isset($token->attr['href'])) {
            return;
        }

        // sanitize URL
        // NOTE it is not clear whether sanitization can be left away
        $url = $this->sanitizer->validate(
            $token->attr['href'],
            $this->config,
            $this->context
        );
        $token->attr['href'] = $url;

        // classify URL
        $token->attr['class'] = isLinkIntern($url) ? 'link-intern' : 'link-extern';
    }
}
