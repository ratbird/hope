<section class="oauth authorize">
    <p>
        <?= sprintf(_('Die Applikation <strong>%s</strong> m�chte auf Ihre Daten zugreifen.'), 
                    htmlReady($consumer->title)) ?>
    </p>

    <form action="<?= $controller->url_for('api/oauth/authorize?oauth_token=' . $token) ?>" method="post">
        <p>
            <?= Studip\Button::createAccept(_('Erlauben'), 'allow') ?>
            <?= Studip\LinkButton::createCancel(_('Verweigern'), $consumer->callback) ?>
        </p>
    </form>

    <p>
        <?= Avatar::getAvatar($GLOBALS['user']->id)->getImageTag(Avatar::SMALL) ?>

        <?= sprintf(_('Angemeldet als <strong>%s</strong> (%s)'),
                    $name = get_fullname(), $GLOBALS['user']->username) ?><br>
        <small>
            <?= sprintf(_('Sind sie nicht <strong>%s</strong>, so <a href="%s">melden Sie sich bitte ab</a> und versuchen es erneut.'),
                        $name, URLHelper::getLink('logout.php')) ?>
        </small>
    </p>
</section>
