<? use Studip\Button, Studip\LinkButton;


                object_set_visit($evalID, "eval"); //set a visittime for this eval

                echo createBoxContentHeader ();
                echo createFormHeader ($eval);

                /* User has already used the vote --------------------------------- */
                $hasVoted = $evalDB->hasVoted ($evalID, $userID);
                $numberOfVotes = $evalDB->getNumberOfVotes ($evalID);
                $evalNoPermissons = EvaluationObjectDB::getEvalUserRangesWithNoPermission($eval);

                $table = new HTML ("table");
                $table->addAttr("style", "font-size:1.2em;");
                $table->addAttr("width", "100%");
                $table->addAttr("border", "0");
                $tr = new HTML ("tr");
                $td = new HTML ("td");

                $maxTitleLength = ($isHomepage)
                        ? VOTE_SHOW_MAXTITLELENGTH
                        : VOTE_SHOW_MAXTITLELENGTH - 10;

                if (strlen (formatReady($eval->getTitle ())) > $maxTitleLength) {
                    $b = new HTML ("b");
                    $b->addHTMLContent(formatReady($eval->getTitle ()));

                    $td->addContent($b);
                    $td->addContent( new HTMLempty ("br") );
                    $td->addContent( new HTMLempty ("br") );
                }

                $td->addAttr("style", "font-size:0.8em;");
                $td->addHTMLContent(formatReady($eval->getText ()));
                $td->addContent(new HTMLempty ("br"));
                $td->addContent(new HTMLempty ("br"));

                if (! $hasVoted ) {
                    $div = new HTML ("div");
                    $div->addAttr ("align", "center");
                    $div->addContent (EvalShow::createVoteButton ($eval));
                    $td->addContent ($div);
                }

                $tr->addContent ($td);
                $table->addContent ($tr);
                $table->addContent (EvalShow::createEvalMetaInfo ($eval, $hasVoted));

                if ( $haveFullPerm ) {
                    if (!($range = get_username($rangeID2)))
                        $range = $rangeID2;
                    $tr = new HTML ("tr");
                    $td = new HTML ("td");
                    $td->addAttr ("align", "center");
                    $td->addContent (EvalShow::createOverviewButton ($range, $eval->getObjectID ()));

                    if ( $evalNoPermissons == 0 ) {
                        $td->addContent (EvalShow::createStopButton ($eval));
                        $td->addContent (EvalShow::createDeleteButton ($eval));
                        $td->addContent (EvalShow::createExportButton ($eval));
                        $td->addContent (EvalShow::createReportButton ($eval));
                    }

                    $tr->addContent ($td);
                    $table->addContent ($tr);
                }

                echo $table->createContent ();

?>