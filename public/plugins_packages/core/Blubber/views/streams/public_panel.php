<p>
    <strong><?= _("Öffentlich") ?></strong> - <?= _("jeder darf diesen Blubber sehen.") ?>
</p>
<p style="font-size: 0.8em;">
    <?= _("In der Regel sehen diejenigen Leute diesen Blubber in ihrem globalen Stream, die untenstehenden Personen als Kontakt hinzugefügt haben. Aber theoretisch darf jede NutzerIn den Blubber und die Diskussion dazu sehen.") ?>
</p>
<hr>
<? $i_shared = false ?>
<?= _("Folgende Personen haben diesen Blubber geteilt:") ?>
<ul class="blubber_contacts">
    <li>
        <? $author = $thread->getUser() ?>
        <? if ($author['user_id'] === $GLOBALS['user']->id) $i_shared = true ?>
        <a href="<?= URLHelper::getLink($author->getURL()) ?>">
            <?= $author->getAvatar()->getImageTag(Avatar::MEDIUM, array('title' => $author->getName())) ?>
        </a>
    </li>
    <? foreach ($thread->getSharingUsers() as $user) : ?>
    <? if ($user['user_id'] === $GLOBALS['user']->id) $i_shared = true ?>
    <li>
        <a href="<?= URLHelper::getLink($user->getURL()) ?>">
            <?= $user->getAvatar()->getImageTag(Avatar::MEDIUM, array('title' => $user->getName())) ?>
        </a>
    </li>
    <? endforeach ?>
    <? if (!$i_shared) : ?>
    <li class="want_to_share">
        <?= Assets::img("icons/24/blue/add", array('title' => _("Weitersagen / teilen"))) ?>
    </li>
    <? endif ?>
</ul>
<hr>
<? if (!$i_shared && $GLOBALS['user']->id !== "nobody") : ?>
<?= \Studip\LinkButton::create(_("weitersagen"), "share", array('href' => "?#")) ?>
<? endif; ?>
<br><br>