<?php
$i=0;
$fieldText = '';
foreach ($filter->getFields() as $field) {
    if ($i > 0) {
        $fieldText .= ' <b>'._('und').'</b> ';
    }
    $valueNames = $field->getValidValues();
    $fieldText .= $field->getName()." ".$field->getCompareOperator().
        " " . (count($valueNames) ? $valueNames[$field->getValue()] : $field->getValue());
    $i++;

}
if ($filter->show_user_count) {
    $user_count = count($filter->getUsers());
    $fieldText .= ' ('.sprintf(_('%s Personen'), $user_count);
    if (!$user_count) {
        $fieldText .= Assets::img('icons/16/red/exclaim-circle.png', array('title' => _("Kein Nutzer erfüllt diese Bedingung.")));
    }
    $fieldText .= ')';
}
echo $fieldText;
?>