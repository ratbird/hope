<? # Lifter005: TODO - studipim ?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=WINDOWS-1252">
    <? if (basename($_SERVER['SCRIPT_NAME']) !== 'logout.php' &&
           $auth->auth["uid"] !== "" && $auth->auth["uid"] !== "nobody" && $auth->auth["uid"] !== "form" &&
           $GLOBALS['AUTH_LIFETIME'] > 0) : ?>
      <meta http-equiv="REFRESH" CONTENT="<?= $GLOBALS['AUTH_LIFETIME'] * 60 ?>; URL=<?= $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] ?>logout.php">
    <? endif ?>
    <link rel="shortcut icon" href="<?= Assets::url('images/favicon.ico') ?>">
    <title>
      <? if (isset($GLOBALS['_html_head_title'])): ?>
    <?= $GLOBALS['_html_head_title'] ?>
      <? elseif (isset($GLOBALS['CURRENT_PAGE'])): ?>
    <?= $GLOBALS['HTML_HEAD_TITLE'] ?> - <?= $GLOBALS['CURRENT_PAGE'] ?>
      <? else: ?>
    <?= $GLOBALS['HTML_HEAD_TITLE'] ?>
      <? endif ?>
    </title>

    <?
      if (!isset($GLOBALS['_include_stylesheet'])) {
        $GLOBALS['_include_stylesheet'] = 'style.css';
      }
    ?>
    <? if ($GLOBALS['_include_stylesheet'] != '') : ?>
      <?= Assets::stylesheet($GLOBALS['_include_stylesheet'], array('media' => 'screen, print')) ?>
    <? endif ?>

    <? if (isset($GLOBALS['_include_extra_stylesheet'])) : ?>
      <?= Assets::stylesheet($GLOBALS['_include_extra_stylesheet']) ?>
    <? endif ?>

    <?= Assets::stylesheet('header', array('media' => 'screen, print')) ?>
    <?= Assets::stylesheet('jquery-ui.1.8.css', array('media' => 'screen, print')) ?>

    <?= Assets::script('jquery-1.4.2.min.js', 'jquery-ui-1.8.custom.min.js',
                       'jquery.metadata.js', 'l10n.js', 'application') ?>

    <script src="<?= URLHelper::getLink('dispatch.php/localizations/' . $GLOBALS['_language']) ?>"></script>

    <script>
      STUDIP.ABSOLUTE_URI_STUDIP = "<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] ?>";
      STUDIP.ASSETS_URL = "<?= $GLOBALS['ASSETS_URL'] ?>";
      String.locale = "<?= strtr($GLOBALS['_language'], '_', '-') ?>";
    </script>

    <? if ($GLOBALS['my_messaging_settings']['start_messenger_at_startup'] &&
           $GLOBALS['auth']->auth['jscript'] &&
           !$_SESSION['messenger_started'] &&
           !$GLOBALS['seminar_open_redirected']) : ?>
      <script>
        fenster = window.open("studipim.php", "im_<?= $GLOBALS['user']->id ?>",
                              "scrollbars=yes,width=400,height=300",
                              "resizable=no");
      </script>
      <? $_SESSION['messenger_started'] = TRUE; ?>
    <? endif ?>

    <? if (isset($GLOBALS['_include_additional_header'])) : ?>
      <?= $GLOBALS['_include_additional_header'] ?>
    <? endif ?>
  </head>

 <body<?= (isset($GLOBALS['body_id']) ? ' id="'.htmlReady($GLOBALS['body_id']).'"' : '') .
          (isset($GLOBALS['body_class']) ? ' class="'.htmlReady($GLOBALS['body_class']).'"' : '' ) ?>>
    <?= isset($GLOBALS['_include_additional_html']) ? $GLOBALS['_include_additional_html'] : '' ?>
    <div id="overdiv_container"></div>

    <div id="ajax_notification">
      <?= Assets::img('ajax_indicator.gif') ?> <?= _('Wird geladen') ?>&hellip;
    </div>

    <? include 'lib/include/header.php'; ?>

    <div id="layout_container" style="padding: 1em;">
        <?= $content_for_layout ?>
        <div class="clear"></div>
    </div>
  </body>
</html>
