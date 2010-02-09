<? if ($error) : ?>
    <em><?= _("keine. Na sowas. Das kann ja eigentlich gar nicht sein...") ?></em>
<? else : ?>
    <ul>
        <? foreach($users as $user) : ?>
            <li>
                <a href="<?= URLHelper::getLink('about.php',
                                                 array('username' => $user['username'])) 
                          ?>"><?= htmlReady($user['fullname']) ?></a>, E-Mail:
                <?= FixLinks(htmlReady($user['Email'])) ?>
            </li>
        <? endforeach ?>
    </ul>
<? endif ?>
