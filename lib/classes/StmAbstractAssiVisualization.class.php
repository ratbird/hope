<?php
# Lifter002: TODO
# Lifter007: TODO
# Lifter003: TODO
# Lifter010: TODO


require_once("lib/classes/AbstractStm.class.php");
require_once("lib/classes/AbstractStmElement.class.php");
require_once("lib/wiki.inc.php");

class StmAbstractAssiVisualization {

    function showSelAbsStmForm($form) {

        $styles = array(    'width'     => '100%',
//                          'border'        => '1',
                            'align'     => 'center'
        );

        $table = new Table($styles);

        echo $table->open(array('class' => 'steelgraulight'));
        $table->setCellColspan(1);
        echo $table->openRow();
            echo $table->cell(
            "<b>" . _("Wahl eines Allgemeinen Moduls") . "</b><br>"
            . _("Bitte w&auml;hlen Sie das Modul aus, welches Sie editieren wollen oder l&ouml;schen Sie ein Allgemeines Modul mit dem entsprechenden Knopf. Sie k&ouml;nnen alternativ auch ein neues Modul anlegen. Beachten Sie bitte, dass Sie nur Allgemeine Module bearbeiten und l&ouml;schen k&ouml;nnen, zu denen kein Konkretes Modul existiert!")
            , array('align' => 'center', 'class' => 'blank'));
            echo $table->cell(Assets::img('infobox/archiv.jpg'));
        echo $table->close();

        echo $table->open(array('class' => 'steelgraulight'));
        echo $form->getFormStart();
        echo $table->blankRow();
        $table->setCellColspan(4);
        echo $table->cell($form->getFormButton("neuanlegen"), array('align' => 'center'));
        $table->setCellColspan(1);
        echo $table->blankRow();
        foreach ($form->form_fields as $name => $field) {
            echo $table->openRow();
            echo $table->cell($form->getFormButton("sel_$name") . '&nbsp;');
            echo $table->cell($form->getFormButton("del_$name") . '&nbsp;');
            echo $table->cell($form->getFormButton("info_$name"). '&nbsp;');
            echo $table->cell($form->form_fields[$name]['info']);
            echo $table->closeRow();
        }
        //echo $table->blankRow();
        //$table->setCellColspan(4);
        //echo $table->cell($form->getFormButton("neuanlegen"), array('align' => 'center'));
        //echo $table->blankRow();
        echo $table->close();
        echo $table->open(array('class' => 'steelgraulight'));

        echo $form->getFormStart();

        echo $form->getFormEnd();
        echo $table->close();
    }

    function showInputForm($form) {

        $styles = array(    'width'     => '100%',
//                          'border'        => '1',
                            'align'     => 'center'
        );

        $table = new Table($styles);

        $star = "";

        echo $table->open(array('class' => 'steelgraulight'));
        $table->setCellColspan(1);
        echo $table->openRow();
            echo $table->cell(
            "<b>" . _("Grunddaten des Allgemeinen Moduls bearbeiten") . "</b><br><br>
            " . _("Hier werden die allgemeinen Informationen des Moduls eingetragen. Diese k&ouml;nnen direkt aus dem Modulhandbuch entnommen werden.")
            , array('align' => 'center', 'width' => '50%', 'class' => 'blank'));
            echo $table->cell('<img src="'.$GLOBALS['ASSETS_URL'].'images/hands01_04.jpg" border="0" align=right">', array('align' => 'right', 'width' => '70%', 'class' => 'blank'));
        echo $table->openRow();
        echo $table->cell('<font size=-1>Alle mit einem Sternchen <font size=+1 color="red"><b>*</b></font> markierten Felder <b>m&uuml;ssen</b> ausgef&uuml;llt werden.</font>', array('align => right', 'colspan' => '2', 'class'=>'steel1'));
        echo $table->closeRow();
        echo $table->blankRow();

        echo $table->close();
        echo $table->open(array('class' => 'steelgraulight'));

        echo $form->getFormStart();
        $infobox = array ();
        $infobox[] = array("kategorie" => _("Information"), "eintrag" => array(array("icon"=>"icons/16/black/info.png",
        "text"=> sprintf(_("Die Textfelder <b>Inhalte, Lernziele</b> und <b>Hinweise</b> m&uuml;ssen nach den Konventionen
        des StudIPWiki formatiert werden. Beachten Sie dabei die %sFormatierungsm&ouml;glichkeiten%s. <br>
        Der Button <b>Vorschau</b> zeigt das Ergebnis unter dem jeweiligen Textfeld an.")
        ,'<a href="help/index.php?help_page=ix_forum6.htm" target="_blank">','</a>'))));
        foreach($form->form_fields as $name => $value)
        {
            echo $table->openRow();
            echo $table->cell('<b>' . $form->getFormFieldCaption($name) .'</b>' . $form->getFormFieldRequired($name) . "<br>" .
            $value['info']
            , array('style' => 'width: 30%', 'align' => 'center'));

            echo $table->cell($form->getFormField($name, array('rows' => '18', 'style' => 'width: 90%; vertical-align: middle')) .'<br><br>', array( 'align' => 'left'));
            if ($name == 'topics') {
                echo $table->openCell(array('align' => 'center'));
                print_infobox ($infobox, "infobox/wiki.jpg");
                echo '<br>Vorschau&nbsp;' . $form->getFormButton('preview');
                echo $table->closeCell();
            }

            echo $table->closeRow();
            if ($name == 'topics' || $name=='aims' || $name=='hints') {
                $table->setCellAlign('left');
                if ($form->form_values[$name] != '') {
                    echo $table->openRow();
                    echo $table->blankCell();
                    echo $table->cell('<hr>');
                    echo $table->openRow();
                    echo $table->blankCell();
                    echo $table->cell(wikiReady($form->form_values[$name]));
                    echo $table->openRow();
                    echo $table->blankCell();
                    echo $table->cell('<hr>');
                }
                $table->setCellAlign('center');
                $table->setCellColspan(1);
                echo $table->blankRow();
            }
        }

        echo $table->blankRow();
        echo $table->openRow();
        $table->setCellAlign('center');
        $table->setCellColspan(2);
        echo $table->openCell();
        echo $form->getFormButton('back');
        echo $form->getFormButton('reset');
        echo $form->getFormButton('continue');
        echo $table->openCell();
        echo $table->closeRow();

        echo $form->getFormEnd();
        echo $table->close();
    }

    function showElementsInputForm($form, $elements) {

        $types = AbstractStmElement::GetStmElementTypes();
        $blocks = count($elements);
        $styles = array(    'width'     => '100%',
                            'align'     => 'center',

        );

        $table = new Table($styles);

        echo $table->open(array('class' => 'steelgraulight'));
        $table->setCellColspan(1);
        echo $table->openRow();
            echo $table->cell(
            "<b>" . _("Zuweisen von Modulbestandteilen (Allgemeinen Veranstaltungen)") . "</b><br><br>
            " . _("Hier werden dem Allgemeinen Modul Veranstaltungen zugewiesen. Jedes Feld mit seinen Zeilen entspricht dabei einer m&ouml;glichen Kombination von Veranstaltungen. Wird das Modul sp&auml;ter konkret f&uuml;r ein Semester angelegt, k&ouml;nnen sowohl verschiedene Kombinationen, als auch eine Kombination mehrmals mit realen Veranstaltungen belegt werden.")
            , array('align' => 'center', 'class' => 'blank'));
            $table->setCellColspan(2);
            echo $table->cell('<img src="'.$GLOBALS['ASSETS_URL'].'images/hands02_04.jpg" border="0" align=right">', array('align' => 'right', 'class' => 'blank'));
        echo $table->close();

        echo $table->open(array('class' => 'steelgraulight'));
        $table->setCellColspan(5);
        echo $table->openRow(array('class' => 'steel1'));
            echo $table->cell("<font size=-1>Bitte f&uuml;gen Sie erst alle Zeilen mit dem entsprechenden Knopf zu den Kombinationen hinzu, bevor Sie auf \"weiter\" klicken.</font>");
        echo $form->getFormStart();

        for ($i=0; $i<$blocks; $i++) {
            $table->setCellColspan(1);
            echo $table->openRow(array('align' => 'center',  'class' => 'steelgraudunkel'));
                echo $table->cell($form->getFormFieldCaption('sws_' . $i) , array('style' =>  'font-weight: bold'));
                echo $table->cell($form->getFormFieldCaption('element_type_id_' . $i) , array('style' =>  'font-weight: bold'));
                echo $table->cell($form->getFormFieldCaption('workload_' . $i) , array('style' =>  'font-weight: bold'));
                echo $table->cell($form->getFormFieldCaption('semester_' . $i) , array('style' =>  'font-weight: bold'));
                echo $table->blankCell();
            echo $table->blankRow();
            foreach($elements[$i] as $index => $elem) {
                echo $table->openRow(array('style' => 'text-align: center'));
                echo $table->cell($elem->getSws());
                echo $table->cell($types[$elem->getElementTypeId()]['name']);
                echo $table->cell($elem->getWorkload());
                echo $table->cell(($elem->getSemester()==0?"kein":($elem->getSemester()==1?"Sommersemester":"Wintersemester")));
                echo $table->cell($form->getFormButton('remove_elem_' . $i . '_' . $index) ." &nbsp;");
            }
            echo $table->openRow();
                echo $table->cell($form->getFormField('sws_' . $i) , array('style' => 'text-align: center'));
                echo $table->cell($form->getFormField('element_type_id_' . $i) , array('style' => 'text-align: center'));
                echo $table->cell($form->getFormField('workload_' . $i) , array('style' => 'text-align: center'));
                echo $table->cell($form->getFormField('semester_' . $i) , array('style' => 'text-align: center'));
            echo $table->blankRow();
            $table->setCellColspan(5);
            echo $table->row(array($table->cell($form->getFormButton('add_elem_' . $i), array('style' => 'text-align: center'))));
            echo $table->blankRow();
            if ($i !=0)
                echo $table->row(array($table->cell('&nbsp;' . $form->getFormButton('remove_block_' . $i), array('style' => 'text-align: left', 'class' => 'steel1'))));
            else
            echo $table->blankRow(array('class' => 'steel1'));
        }

        echo $table->row(array($table->cell('&nbsp;' . $form->getFormButton('add_block'), array('style' => 'text-align: left', 'class' => 'steel1'))));
        echo $table->blankRow(array('class' => 'steel1'));
        echo $table->openRow();
            echo $table->cell('<h3>Neue Lehr- und Lernform anlegen</h3>');
        $table->setCellColspan(5);
        echo $table->openRow();
            echo $table->openCell(array('style' =>  'text-align:center'));
            echo $form->getFormFieldCaption('new_element_name', array('style' =>  'font-weight: bold'));
            echo $form->getFormField('new_element_name');
            echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            echo $form->getFormFieldCaption('new_element_abbrev', array('style' =>  'font-weight: bold'));
            echo $form->getFormField('new_element_abbrev');
            echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            echo $form->getFormButton('add_element_type');
        echo $table->blankRow();


        $table->setCellColspan(5);
        echo $table->openRow(array('class' => 'steel1'));
        $table->setCellAlign('center');
        echo $table->openCell();
            echo $form->getFormButton('back');
            echo $form->getFormButton('continue');
            echo $form->getFormButton('reset');
        echo $table->closeCell();
        echo $table->closeRow();

        echo $form->getFormEnd();
        echo $table->close();
    }

    function showAssignForm($form, $assigns) {

        $asq_selected = false;
        $stgs = AbstractStm::GetStg();
        $abschluesse = AbstractStm::GetAbschluesse();
        $types = AbstractStm::GetAbsStmTypes();
        $pversions = AbstractStm::GetPversions();

        $styles = array(    'width'     => '100%',
//                          'border'        => '2',
                            'align'     => 'center'

        );

        $table = new Table($styles);

        echo $table->open(array('class' => 'steelgraulight'));
        $table->setCellColspan(1);
        echo $table->openRow();
            echo $table->cell(
            "<b>" . _("Studienprogrammverwendbarkeit") . "</b><br><br>
            " . _("Hier werden dem Allgemeinen Modul Studienprogramme zugewiesen. Da es nur bestimmte Kombinationen von Studiengang (Abschluss) und Studienprogramm (Fach) gibt, &auml;ndert sich die Auswahl der Studienprogramme je nach Wahl des Studiengangs.")         , array('align' => 'center', 'class' => 'blank'));
            echo $table->cell('<img src="'.$GLOBALS['ASSETS_URL'].'images/hands03_04.jpg" border="0" align=right">', array('align' => 'right', 'class' => 'blank'));
        echo $form->getFormStart();
        echo $table->close();

        echo $table->open(array('class' => 'steelgraulight'));
        $table->setCellColspan(8);
        echo $table->openRow(array('class' => 'steel1'));
            echo $table->cell("<font size=-1>Bitte f&uuml;gen Sie erst alle Zeilen mit dem entsprechenden Knopf hinzu, bevor Sie auf \"weiter\" klicken.</font>");
        echo $form->getFormStart();

        $table->setCellColspan(1);
        echo $table->openRow(array('align' => 'center',  'class' => 'steelgraudunkel'));
            echo $table->cell($form->getFormFieldCaption('abschl_list') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form->getFormFieldCaption('stg_list') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form->getFormFieldCaption('type_list') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form->getFormFieldCaption('pversion') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form->getFormFieldCaption('earliest') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form->getFormFieldCaption('latest') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form->getFormFieldCaption('recommed') , array('style' =>  'font-weight: bold'));
            echo $table->blankCell();
        echo $table->blankRow();
        foreach($assigns as $index => $val) {
            echo $table->openRow(array('style' => 'text-align: center'));
            if ($val['abschl']=='asq') {
                echo $table->cell("ASQ");
                $asq_selected = true;
            }
            else
                echo $table->cell($abschluesse[$val['abschl']]);
            echo $table->cell($stgs[$val['stg']]);
            echo $table->cell($types[$val['stm_type_id']]['name']);
            echo $table->cell($pversions[$val['pversion']]);
            echo $table->cell($val['earliest']);
            echo $table->cell($val['latest']);
            echo $table->cell($val['recommed']);
            echo $table->cell($form->getFormButton('remove_' . $index));
        }
        if (!$asq_selected) {
            echo $table->openRow();
                echo $table->cell($form->getFormField('abschl_list', array('onchange' => 'this.form.submit()', 'style' => 'width: 210')) , array('style' => 'text-align: center'));
                echo $table->cell($form->getFormField('stg_list', array('onchange' => 'this.form.submit()', 'style' => 'width: 210')) , array('style' => 'text-align: center'));
                echo $table->cell($form->getFormField('type_list') , array('style' => 'text-align: center'));
                echo $table->cell($form->getFormField('pversion', array('style' => 'width: 150')) , array('style' => 'text-align: center'));
                echo $table->cell($form->getFormField('earliest') , array('style' => 'text-align: center'));
                echo $table->cell($form->getFormField('latest') , array('style' => 'text-align: center'));
                echo $table->cell($form->getFormField('recommed') , array('style' => 'text-align: center'));
                echo $table->blankCell();
                echo $table->blankRow();
            $table->setCellColspan(8);
            echo $table->row(array($table->cell($form->getFormButton('add'), array('style' => 'text-align: center'))));
        }
        $table->setCellColspan(8);
        echo $table->blankRow();
        echo $table->blankRow(array('class' => 'steel1'));
        echo $table->openRow(array('class' => 'steel1'));
        $table->setCellAlign('center');
        echo $table->openCell();
            echo $form->getFormButton('back');
            echo $form->getFormButton('continue');
            echo $form->getFormButton('reset');
        echo $table->closeCell();
        echo $table->closeRow();

        echo $form->getFormEnd();
        echo $table->close();
    }

    function showSummaryForm($form, $stm, $form1, $form2, $form3) {

        $elem_types = AbstractStmElement::GetStmElementTypes();
        $stm_types = AbstractStm::GetAbsStmTypes();
        $stgs = AbstractStm::GetStg();
        $abschluesse = AbstractStm::GetAbschluesse();
        $pversions = AbstractStm::GetPversions();

        $styles = array(    'width'     => '100%',
                            //'border'  => '2',
                            'align'     => 'center'

        );

        $table = new Table($styles);

        echo $table->open(array('class' => 'blank'));
        $table->setCellColspan(1);
        echo $table->openRow();
            echo $table->cell(
            "<b>" . _("Zusammenfassung") . "</b><br><br>
            " . _("Hier werden alle Daten des Allgemeinen Moduls dargestellt. Bitte &uuml;berpr&uuml;fen Sie die Angaben und fahren dann entsprechend fort.")
            , array('align' => 'center', 'class' => 'blank'));
            echo $table->cell('<img src="'.$GLOBALS['ASSETS_URL'].'images/hands04_04.jpg" border="0" align=right">', array('align' => 'right', 'class' => 'blank'));
        echo $form->getFormStart();
        echo $table->setCellColspan(2);
        echo $table->openRow(array('class' => 'steel1'));
            echo $table->blankCell();
        echo $table->close();
        echo $table->open(array('class' => 'steelgraulight'));
        echo $table->blankRow();
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b>" . $form1->getFormFieldCaption('title') . "</b>&nbsp;", array('align' => 'left', 'style' => 'width:12%'));
            echo $table->cell($stm->getTitle(), array('style' => 'width:35%'));
            echo $table->blankCell(array('style' => 'width:2%'));
            echo $table->cell("&nbsp;&nbsp;<b>" . $form1->getFormFieldCaption('credits') . "</b>&nbsp;", array('align' => 'left', 'style' => 'width:12%'));
            echo $table->cell($stm->getCredits(), array('style' => 'width:35%'));
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b>" . $form1->getFormFieldCaption('subtitle') . "</b>&nbsp;", array('align' => 'left', 'style' => 'width:12%'));
            echo $table->cell($stm->getSubtitle(), array('style' => 'width:35%'));
            echo $table->blankCell(array('style' => 'width:2%'));
            echo $table->cell("&nbsp;&nbsp;<b>" . $form1->getFormFieldCaption('workload') . "</b>&nbsp;", array('align' => 'left', 'style' => 'width:12%'));
            echo $table->cell($stm->getWorkload(), array('style' => 'width:35%'));
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b>" . $form1->getFormFieldCaption('id_number') . "</b>&nbsp;", array('align' => 'left', 'style' => 'width:12%'));
            echo $table->cell($stm->getIdNumber(), array('style' => 'width:35%'));
            echo $table->blankCell(array('style' => 'width:2%'));
            echo $table->cell("&nbsp;&nbsp;<b>" . $form1->getFormFieldCaption('turnus') . "</b>&nbsp;", array('align' => 'left', 'style' => 'width:15%'));
            echo $table->cell(($stm->getTurnus()==0?"kein":($stm->getTurnus()==1?_("Sommersemester"):_("Wintersemester"))), array('style' => 'width:40%'));
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b>" . $form1->getFormFieldCaption('duration') . "</b>&nbsp;", array('align' => 'left', 'style' => 'width:15%'));
            echo $table->cell($stm->getDuration(), array('style' => 'width:35%'));
        echo $table->blankRow();
        echo $table->openRow();
            echo $table->blankCell();
            $table->setCellColspan(9);
            echo $table->cell("<hr>");
            $table->setCellColspan(2);
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b>" . $form1->getFormFieldCaption('topics') . "</b>&nbsp;", array('align' => 'left', 'style' => 'width:15%'));
            echo $table->cell(wikiReady($stm->getTopics()), array('style' => 'width:35%'));
            echo $table->blankCell(array('style' => 'width:2%'));
            echo $table->cell("&nbsp;&nbsp;<b>" . $form1->getFormFieldCaption('aims') . "</b>&nbsp;", array('align' => 'left', 'style' => 'width:15%'));
            echo $table->cell(wikiReady($stm->getAims()), array('style' => 'width:35%'));
        echo $table->openRow();
            echo $table->blankCell();
            $table->setCellColspan(9);
            echo $table->cell("<hr>");
            $table->setCellColspan(2);
        echo $table->openRow();
            echo $table->cell("&nbsp;&nbsp;<b>" . $form1->getFormFieldCaption('hints') . "</b>&nbsp;", array('align' => 'left', 'style' => 'width:15%'));
            echo $table->cell(wikiReady($stm->getHints()), array('style' => 'width:35%'));
        echo $table->blankRow();
        echo $table->close();
        echo $table->open(array('class' => 'steelgraulight'));
        $table->setCellColspan(1);
        echo $table->openRow(array('align' => 'center',  'class' => 'steelgraudunkel'));
            echo $table->cell($form2->getFormFieldCaption('element_type_id_0') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form2->getFormFieldCaption('sws_0') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form2->getFormFieldCaption('workload_0') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form2->getFormFieldCaption('semester_0') , array('style' =>  'font-weight: bold'));
        foreach($stm->elements as $i => $elem_list) {
            foreach($elem_list as $j => $elem) {
                echo $table->openRow(array('style' => 'text-align: center'));
                echo $table->cell($elem_types[$elem->getElementTypeId()]['name']);
                echo $table->cell($elem->getSws());
                echo $table->cell($elem->getWorkload());
                echo $table->cell(($elem->getSemester()==0?"kein":($elem->getSemester()==1?_("Sommersemester"):_("Wintersemester"))));
            }
            echo $table->openRow();
            echo $table->cell("<hr>", array('colspan' => '4'));
        }
        echo $table->blankRow();
        echo $table->close();
        echo $table->open(array('class' => 'steelgraulight'));
        $table->setCellColspan(1);
        echo $table->openRow(array('align' => 'center',  'class' => 'steelgraudunkel'));
            echo $table->cell($form3->getFormFieldCaption('abschl_list') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form3->getFormFieldCaption('stg_list') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form3->getFormFieldCaption('type_list') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form3->getFormFieldCaption('pversion') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form3->getFormFieldCaption('earliest') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form3->getFormFieldCaption('latest') , array('style' =>  'font-weight: bold'));
            echo $table->cell($form3->getFormFieldCaption('recommed') , array('style' =>  'font-weight: bold'));
            echo $table->blankCell();
        foreach($stm->assigns as $index => $val) {
            echo $table->openRow(array('style' => 'text-align: center'));
            if ($val['abschl']=='asq')
                echo $table->cell("ASQ");
            else
                echo $table->cell($abschluesse[$val['abschl']]);
            echo $table->cell($stgs[$val['stg']]);
            echo $table->cell($stm_types[$val['stm_type_id']]['name']);
            echo $table->cell($pversions[$val['pversion']]);
            echo $table->cell($val['earliest']);
            echo $table->cell($val['latest']);
            echo $table->cell($val['recommed']);
        }

        echo $table->close();
        echo $table->open(array('class' => 'steel1'));
        echo $table->blankRow();
        echo $table->openRow();
        $table->setCellAlign('center');
        echo $table->openCell();
            echo $form->getFormButton('back');
            if($form->form_buttons['continue']) echo $form->getFormButton('continue');
        echo $table->openCell();
        echo $table->closeRow();

        echo $form->getFormEnd();
        echo $table->close();
    }

    function showError($err_msg) {

        if (count($err_msg)){
            echo "\n<table width=\"99%\" border=\"0\" cellpadding=\"2\" cellspacing=\"0\">";
            parse_msg_array($err_msg, "blank", 1 ,false);
            echo "\n</table>";
        }

    }


}
?>
