<?= _("Öffentlich: Jeder darf diesen Blubber sehen.") ?>
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
</ul>
<hr>
<? if (!$i_shared && $GLOBALS['user']->id !== "nobody") : ?>
<?= \Studip\LinkButton::create(_("weitersagen"), "share", array('href' => "?#")) ?>
<? endif; ?>
<br><br>