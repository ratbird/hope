<table class="default">
    <tr>
        <th width="50%"><?= _('Hauptnavigation') ?></th>
        <th width="50%"><?= _('Zusatznavigation') ?></th>
    </tr>
    <tr class="steel1">
        <td valign="top">
        <ul>
        <? foreach($navigation as $nav) : ?>
            <li><b><a href="<?= URLHelper::getLink($nav->getURL(), $link_params) ?>"> <?= htmlReady($nav->getTitle()) ?></a></b>
            <? if(count($nav->getSubNavigation()) > 0) : ?>
            <ul>
            <? foreach ($nav->getSubNavigation() as $subnav) : ?>
                <li><a href="<?= URLHelper::getLink($subnav->getURL(), $link_params) ?>"> <?= htmlReady($subnav->getTitle()) ?></a>
                <? if(count($nav->getSubNavigation()) > 0) : ?>
                <ul>
                <? foreach ($subnav->getSubNavigation() as $subsubnav) : ?>
                    <li><a href="<?= URLHelper::getLink($subsubnav->getURL(), $link_params) ?>"> <?= htmlReady($subsubnav->getTitle()) ?></a></li>
                <? endforeach ?>
                </ul>
                <? endif ?>
                </li>
            <? endforeach ?>
            </ul>
            <? endif ?>
            </li>
        <? endforeach ?>
        </ul>
        </td>
        <td valign="top">
        <ul>
        <? foreach($subnavigation as $nav) : ?>
            <li><b><a href="<?= URLHelper::getLink($nav->getURL(), $link_params) ?>"> <?= htmlReady($nav->getTitle()) ?></a></b>
            <ul>
            <? foreach ($nav->getSubNavigation() as $subsubnav) : ?>
                <li><a href="<?= URLHelper::getLink($subsubnav->getURL(), $link_params) ?>"> <?= htmlReady($subsubnav->getTitle()) ?></a>
                </li>
            <? endforeach ?>
            </ul>
            </li>
        <? endforeach ?>
        </ul>
        </td>
    </tr>
</table>
