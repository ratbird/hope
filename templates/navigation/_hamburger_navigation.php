<ol class="subnavigation">
    <? foreach ($nav->getSubNavigation() as $subpath => $subnav) : ?>
        <li>
            <? $checkbox_id = "checkbox_".$path."_".$subpath ?>
            <div class="navigation_item">
                <div class="nav_title">
                    <label for="<?= $checkbox_id ?>">
                        <?= htmlReady($subnav->getTitle()) ?>
                    </label>
                </div>
                <a class="nav_link" href="<?= URLHelper::getLink($subnav->getURL()) ?>">
                    <?= Assets::img("icons/20/white/arr_1right", array('class' => "text-bottom")) ?>
                </a>
            </div>
            <input type="checkbox" id="<?= $checkbox_id ?>" style="display: none;"<?= $nav->isActive() ? " checked" : "" ?>>
            <?= $this->render_partial("navigation/_hamburger_navigation.php", array('path' => $path."_".$subpath, 'nav' => $subnav)) ?>
        </li>
    <? endforeach ?>
</ol>