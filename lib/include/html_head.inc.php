<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
/**
* html_head.inc.php
*
* output of html-head for all Stud.IP pages<br>
*
* @author       Stefan Suchi <suchi@data-quest.de>
* @access       public
* @package      studip_core
* @modulegroup  library
* @module       html_head.inc.php
*/

// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
// html_head.inc.php
// Copyright (c) 2002 Stefan Suchi <suchi@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=WINDOWS-1252">
        <title>
            <?= $GLOBALS['HTML_HEAD_TITLE'] ?> - <?= htmlReady(PageLayout::getTitle()) ?>
        </title>
        <?= PageLayout::getHeadElements() ?>

        <script>
            STUDIP.ABSOLUTE_URI_STUDIP = "<?= $GLOBALS['ABSOLUTE_URI_STUDIP'] ?>";
            STUDIP.ASSETS_URL = "<?= $GLOBALS['ASSETS_URL'] ?>";
            String.locale = "<?= strtr($GLOBALS['_language'], '_', '-') ?>";
        </script>
    </head>
    <body id="<?= PageLayout::getBodyElementId() ?>">
      <?= PageLayout::getBodyElements() ?>
      <div id="overdiv_container"></div>

    <div id="ajax_notification">
      <?= Assets::img('ajax_indicator.gif') ?> <?= _('Wird geladen') ?>&hellip;
    </div>
