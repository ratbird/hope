<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8">
    <title>Stud.IP: Icon SVG to PNG</title>
    <link href="<?= URLHelper::getLink('assets.php') ?>?stylesheets/style.css" rel="stylesheet" type="text/css">
    <link href="<?= URLHelper::getLink('assets/style.css') ?>" rel="stylesheet" type="text/css">
    <link href="<?= URLHelper::getLink('assets/jquery.miniColors.css') ?>" rel="stylesheet" type="text/css">
</head>
<body>
    <div id="layout_wrapper">
        <div id="layout_page">
            <div id="layout_container">
                <?= $content_for_layout ?>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="<?= URLHelper::getLink('assets.php') ?>?javascripts/jquery/jquery-1.7.js"></script>
    <script type="text/javascript" src="<?= URLHelper::getLink('assets/jquery.miniColors.min.js') ?>"></script>
    <script type="text/javascript" src="<?= URLHelper::getLink('assets/script.js') ?>"></script>
</body>
</html>
