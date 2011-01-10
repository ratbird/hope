<? if (isset($error)): ?>
    <?= MessageBox::error($error) ?>
<? elseif (isset($flash['error'])): ?>
    <?= MessageBox::error($flash['error']) ?>
<? elseif (isset($flash['success'])): ?>
    <?= MessageBox::success($flash['success']) ?>
<? endif ?>
