<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$table = new HTML ("table");
                    $table->addAttr("class", "inday");
                    $table->addAttr("width", "100%");
                    $table->addAttr("border", "0");
                    $tr = new HTML ("tr");
                    $td = new HTML ("td");
                    $td->addAttr ("style", "font-size:0.8em;");
                    $td->addHTMLContent(formatReady($eval->getText ()));
                    $tr->addContent ($td);
                    $table->addContent ($tr);
                    $table->addContent (EvalShow::createEvalMetaInfo ($eval, $hasVoted));
                    $tr = new HTML ("tr");
                    $td = new HTML ("td");
                    $td->addAttr ("align", "center");
                    $td->addContent (EvalShow::createOverviewButton ($rangeID2, $evalID));
                    $td->addContent (EvalShow::createContinueButton ($eval));
                    $td->addContent (EvalShow::createDeleteButton ($eval));
                    $td->addContent (EvalShow::createExportButton ($eval));
                    $td->addContent (EvalShow::createReportButton ($eval));
                    $tr->addContent ($td);
                    $table->addContent ($tr);
                    echo $table->createContent ();
?>
