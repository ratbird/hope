<? if (!$controller->showResult($vote)): ?>
    <?= Studip\Button::create(_('Abstimmen'), 'vote', array('value' => $vote->id)) ?>
    <?= Request::submitted('preview') 
        ? Studip\LinkButton::create(_('Ergebnisse ausblenden'), ContentBoxHelper::href($vote->id, array('preview' => 0))) 
        : Studip\LinkButton::create(_('Ergebnisse'), ContentBoxHelper::href($vote->id, array('preview' => 1)))
    ?>
<? else: ?>
    <?= Request::submitted('sort')
        ? Studip\LinkButton::create(_('Nicht sortieren'), ContentBoxHelper::href($vote->id, array('preview' => 1))) 
        : Studip\LinkButton::create(_('Sortieren'), ContentBoxHelper::href($vote->id, array('preview' => 0, 'sort' => 1)))
    ?>
    <? if ($vote->changeable): ?>
        <?= Studip\LinkButton::create(_('Antwort ändern'), ContentBoxHelper::href($vote->id, array('change' => 1))) ?>
    <? endif; ?>
    <? if ($vote->namesvisibility): ?>
        <?= Request::submitted('revealNames') && $vote->namesvisibility
            ? Studip\LinkButton::create(_('Namen ausblenden'), ContentBoxHelper::href($vote->id, array('revealNames' => 0))) 
            : Studip\LinkButton::create(_('Namen zeigen'), ContentBoxHelper::href($vote->id, array('revealNames' => 1)));
        ?>
    <? endif; ?>
<? endif; ?>