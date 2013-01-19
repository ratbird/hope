<? $url = "javascript:STUDIP.Forum.loadAction('#abolink', '"
    . (ForumAbo::has($constraint['topic_id']) ? 'remove_' : '') 
    . 'abo/'. $constraint['topic_id'] ."')" ?>

<? $text = $constraint['area'] ? _('Diesen Bereich abonnieren') : _('Dieses Thema abonnieren') ?>
<? if ($constraint['depth'] == 0) :
    $text = _('Komplettes Forum abonnieren');
endif ?>

<? if (!ForumAbo::has($constraint['topic_id'])) : ?>
    <?= Studip\LinkButton::create($text, $url,
        array('title' => _('Wenn sie diesen Bereich abonnieren, erhalten Sie eine '
            . 'Stud.IP-interne Nachricht sobald in diesem Bereich '
            . 'ein neuer Beitrag erstellt wurde.'))) ?>
<? else : ?>
    <?= Studip\LinkButton::create(_('Nicht mehr abonnieren'), $url) ?>
<? endif; ?>