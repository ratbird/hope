<?
# Lifter010: TODO
?>
<!-- Start Header -->
<div id="flex-header">
    <div id="header">
        <!--<div id='barTopLogo'>
            <?= Assets::img('logos/logoneu.jpg', array('alt' => 'Logo Uni Göttingen')) ?>
        </div>
         -->
        <div id="barTopFont">
            <?= htmlReady($GLOBALS['UNI_NAME_CLEAN']) ?>
        </div>
        <? SkipLinks::addIndex(_("Hauptnavigation"), 'barTopMenu', 1); ?>
        <ul id="barTopMenu" role="navigation">
            <? $accesskey = 0 ?>
            <? foreach (Navigation::getItem('/') as $path => $nav) : ?>
                <? if ($nav->isVisible(true)) : ?>
                    <?
                    $accesskey_attr = '';
                    $image          = $nav->getImage();

                    if ($accesskey_enabled) {
                        $accesskey      = ++$accesskey % 10;
                        $accesskey_attr = 'accesskey="' . $accesskey . '"';
                        $image['title'] .= "  [ALT] + $accesskey";
                    }

                    ?>
                    <li id="nav_<?= $path ?>"<? if ($nav->isActive()) : ?> class="active"<? endif ?>>
                        <a href="<?= URLHelper::getLink($nav->getURL(), $link_params) ?>" title="<?= $image['title'] ?>" <?= $accesskey_attr ?> data-badge="<?= (int)$nav->getBadgeNumber() ?>">
                            <img class="headericon original" src="<?= $image['src'] ?>">
                            <br>
                            <?= htmlReady($nav->getTitle()) ?>
                        </a>
                    </li>
                <? endif ?>
            <? endforeach ?>
        </ul>
    </div>
    <!-- Stud.IP Logo -->
    <a class="studip-logo" id="barTopStudip" href="http://www.studip.de/" title="Stud.IP Homepage" target="_blank">
        Stud.IP Homepage
    </a>
</div>

<!-- Leiste unten -->
<div id="barBottomContainer" <?= $public_hint ? 'class="public_course"' : '' ?>>
    <div id="barBottomLeft">
        <div class="current_page">
            <?= ($current_page != "" ? _("Aktuelle Seite:") : "") ?>
        </div>
        <div class="hamburger">
            <label for="hamburgerChecker">
                <?= Assets::img("icons/20/white/hamburger-icon", array('class' => "text-bottom")) ?>
            </label>
        </div>

        <input type="checkbox" id="hamburgerChecker" style="display: none;">

        <div id="hamburgerMenu">
            <ol id="hamburgerNavigation">
                <? $root = Navigation::getItem("/") ?>
                <? foreach ($root->getSubNavigation() as $path => $nav) : ?>
                    <? if ($nav->isVisible(true)) : ?>
                        <li>
                            <? $checkbox_id = "checkbox_" . $path ?>
                            <div class="navigation_item">
                                <div class="nav_title">
                                    <? if ($nav->getSubNavigation()) : ?>
                                    <label for="<?= $checkbox_id ?>">
                                        <? endif ?>
                                        <div class="icon">
                                            <? if ($nav->getImage()) : ?>
                                                <? $icon_array = $nav->getImage() ?>
                                                <img src="<?= htmlReady($icon_array['src']) ?>" style="width: 26px; vertical-align: text-bottom;">
                                            <? endif ?>
                                        </div>
                                        <?= htmlReady($nav->getTitle()) ?>
                                        <? if ($nav->getSubNavigation()) : ?>
                                    </label>
                                <? endif ?>
                                </div>
                                <a class="nav_link" href="<?= URLHelper::getLink($nav->getURL()) ?>">
                                    <?= Assets::img("icons/20/white/arr_1right", array('class' => "text-bottom")) ?>
                                </a>
                            </div>
                            <? if ($nav->getSubNavigation()) : ?>
                                <input type="checkbox" id="<?= $checkbox_id ?>" style="display: none;"<?= $nav->isActive() ? " checked" : "" ?>>
                                <?= $this->render_partial("navigation/_hamburger_navigation.php", array('path' => $path, 'nav' => $nav)) ?>
                            <? endif ?>
                        </li>
                    <? endif ?>
                <? endforeach ?>
            </ol>
        </div>
    </div>
    <div id="barBottommiddle">
        <?= ($current_page != "" ? htmlReady($current_page) : "") ?>
        <?= $public_hint ? '(' . htmlReady($public_hint) . ')' : '' ?>
    </div>
    <!-- Dynamische Links ohne Icons -->
    <div id="barBottomright">
        <ul>
            <? if (is_object($GLOBALS['perm']) && PersonalNotifications::isActivated() && $GLOBALS['perm']->have_perm("autor")) : ?>
                <? $notifications = PersonalNotifications::getMyNotifications() ?>
                <? $lastvisit = (int)UserConfig::get($GLOBALS['user']->id)->getValue('NOTIFICATIONS_SEEN_LAST_DATE') ?>
                <li id="notification_container"<?= count($notifications) > 0 ? ' class="hoverable"' : "" ?>>
                    <? foreach ($notifications as $notification) {
                        if ($notification['mkdate'] > $lastvisit) {
                            $alert = true;
                        }
                    } ?>
                    <div id="notification_marker"<?= $alert ? ' class="alert"' : "" ?> title="<?= _("Benachrichtigungen") ?>" data-lastvisit="<?= $lastvisit ?>">
                        <?= count($notifications) ?>
                    </div>
                    <div class="list below" id="notification_list">
                        <ul>
                            <? foreach ($notifications as $notification) : ?>
                                <?= $notification->getLiElement() ?>
                            <? endforeach ?>
                        </ul>
                    </div>
                    <? if (PersonalNotifications::isAudioActivated()) : ?>
                        <audio id="audio_notification" preload="none">
                            <source src="<?= Assets::url('sounds/blubb.ogg') ?>" type="audio/ogg">
                            <source src="<?= Assets::url('sounds/blubb.mp3') ?>" type="audio/mpeg">
                        </audio>
                    <? endif ?>
                </li>
            <? endif ?>
            <? if (isset($search_semester_nr)) : ?>
                <li>
                    <form id="quicksearch" role="search" action="<?= URLHelper::getLink('dispatch.php/search/courses', array('send' => 'yes', 'group_by' => '0') + $link_params) ?>" method="post">
                        <?= CSRFProtection::tokenTag() ?>
                        <script>
                            var selectSem = function (seminar_id, name) {
                                document.location = "<?= URLHelper::getURL("dispatch.php/course/details/", array("send_from_search" => 1, "send_from_search_page" => URLHelper::getURL("dispatch.php/search/courses?keep_result_set=1")))  ?>&sem_id=" + seminar_id;
                            };
                        </script>
                        <?php
                        print QuickSearch::get("search_sem_quick_search", new SeminarSearch())
                            ->setAttributes(array(
                                "title" => sprintf(_('Nach Veranstaltungen suchen (%s)'), htmlready($search_semester_name)),
                                "class" => "quicksearchbox"
                            ))
                            ->fireJSFunctionOnSelect("selectSem")
                            ->noSelectbox()
                            ->render();
                        //Komisches Zeugs, das die StmBrowse.class.php braucht:
                        print '<input type="hidden" name="search_sem_1508068a50572e5faff81c27f7b3a72f" value="1">';
                        //Ende des komischen Zeugs.
                        ?>
                        <input type="hidden" name="search_sem_sem" value="<?= $search_semester_nr ?>">
                        <input type="hidden" name="search_sem_qs_choose" value="title_lecturer_number">
                        <?= Assets::input("icons/16/white/search.png", array('type' => "image", 'class' => "quicksearchbutton", 'name' => "search_sem_do_search", 'value' => "OK", 'title' => sprintf(_('Nach Veranstaltungen suchen (%s)'), htmlready($search_semester_name)))) ?>
                    </form>
                </li>
            <? endif ?>
            <? if (Navigation::hasItem('/links')) : ?>
                <? foreach (Navigation::getItem('/links') as $nav) : ?>
                    <? if ($nav->isVisible()) : ?>
                        <li <? if ($nav->isActive()) echo 'class="active"'; ?>>
                            <a
                                <? if (is_internal_url($url = $nav->getURL())) : ?>
                                    href="<?= URLHelper::getLink($url, $link_params) ?>"
                                <? else : ?>
                                    href="<?= htmlReady($url) ?>" target="_blank"
                                <? endif ?>
                                <? if ($nav->getDescription()): ?>
                                    title="<?= htmlReady($nav->getDescription()) ?>"
                                <? endif; ?>
                                ><?= htmlReady($nav->getTitle()) ?></a>
                        </li>
                    <? endif ?>
                <? endforeach ?>
            <? endif ?>
            <li id="sidebar-menu">
                <?= Assets::img("icons/20/white/hamburger-icon-small", array('class' => "text-bottom")) ?>
            </li>
        </ul>

    </div>
</div>
<!-- Ende Header -->

