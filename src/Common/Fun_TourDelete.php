<?php

function tour_delete($TourId) {
	require_once('Common/CheckPictures.php');
	$TourCode=getCodeFromId($TourId);
	$TableArray=array(
		"ACL" => "AclTournament",
		"AccColors" => "AcTournament",
		"AccEntries" => "AETournament",
		"AccPrice" => "APTournament",
		"AclDetails" => 'AclDtTournament',
        "AclTemplates" => 'AclTeTournament ',
		"AvailableTarget" => "AtTournament",
		"Awarded" => "AwTournament",
		"Awards" => "AwTournament",
		"BackNumber" => "BnTournament",
		"BoinxSchedule" => "BsTournament",
		"ClubTeamScore"=>"CTSTournament",
		"ClubTeam"=>"CTTournament",
		"CasScore"=>"CaSTournament",
		"CasTeam"=>"CaTournament",
		"CasTeamFinal"=>"CTFTournament",
		"CasTeamTarget"=>"CTTTournament",
		"Classes" => "ClTournament",
        'ClassWaEquivalents' => 'ClWaEqTournament',
		"Countries" => "CoTournament",
		"DistanceInformation" => "DiTournament",
		"DocumentVersions" => "DvTournament",
		"Divisions" => "DivTournament",
		"Eliminations" => "ElTournament",
		"Emails" => "EmTournament",
		"Entries" => "EnTournament",
		"EventClass" => "EcTournament",
		"Events" => "EvTournament",
		"FinSchedule" => "FSTournament",
		"FinalReportA" => "FraTournament",
		"Finals" => "FinTournament",
        "FinOdfTiming" => "FinOdfTournament",
		"FinWarmup" => "FwTournament",
		"Flags" => 'FlTournament',
		"GateLog" => "GLTournament",
		"HeartBeat"=>"HbTournament",
		"HhtData" => "HdTournament",
		"HhtEvents" => "HeTournament",
		"HhtSetup" => "HsTournament",
		"IdCardElements" => "IceTournament",
		"IdCards" => "IcTournament",
		"Images" => "ImTournament",
		"IndOldPositions" => "IopTournament",
		"Individuals" => "IndTournament",
        "IskData" => "IskDtTournament",
		"Logs" => "LogTournament",
		"ModulesParameters" => 'MpTournament',
		"OdfDocuments" => 'OdfDocTournament',
		"OdfMessageStatus" => 'OmsTournament',
		"OdfTranslations" => 'OdfTrTournament',
		"OnLineIds" => 'OliTournament', // This table gets deleted and recreated everytime so no need to import/export it but needs to be deleted for housekeeping!
		"Rankings" => 'RankTournament',
		"RecBroken" => 'RecBroTournament',
		"RecTournament" => 'RtTournament',
		"Reviews" => "RevTournament",
		"RoundRobinGrids" => "RrGridTournament",
		"RoundRobinGroup" => "RrGrTournament",
		"RoundRobinLevel" => "RrLevTournament",
		"RoundRobinMatches" => "RrMatchTournament",
		"RoundRobinParticipants" => "RrPartTournament",
		'RunArchery' => 'RaTournament',
		'RunArcheryRank' => 'RarTournament',
		'RunArcheryParticipants' => 'RapTournament',
		"Scheduler" => "SchTournament",
		"Session" => "SesTournament",
		"SubClass" => "ScTournament",
		"TVContents" => "TVCTournament",
		"TVParams" => "TVPTournament",
		"TVRules" => "TVRTournament",
		"TVSequence" => "TVSTournament",
		"TargetFaces" => "TfTournament",
		"TargetGroups" => "TgTournament",
		"TeamComponent" => "TcTournament",
		"TeamDavis" => "TeDaTournament",
		"TeamFinComponent" => "TfcTournament",
        "TeamFinComponentLog" => "TfclTournament",
		"TeamFinals" => "TfTournament",
		"Teams" => "TeTournament",
		"TourRecords" => "TrTournament",
		"Tournament" => "ToId",
		"TournamentDistances" => "TdTournament",
		"TournamentInvolved" => "TiTournament",
		"VegasAwards" => "VaTournament",
	);

	RemoveOdf($TourId, $TourCode);

	foreach($TableArray as $Key=>$Value)
	{
		$Sql = "DELETE FROM " . $Key . " WHERE " . $Value . " = " . StrSafe_DB($TourId);
		safe_w_sql($Sql);
	}
	$Sql = "DELETE FROM Qualifications WHERE QuId NOT IN (SELECT EnId From Entries) ";
	safe_w_sql($Sql);
	$Sql = "DELETE FROM QualOldPositions WHERE QopId NOT IN (SELECT EnId From Entries) ";
	safe_w_sql($Sql);
	$Sql = "DELETE FROM ElabQualifications WHERE EqId NOT IN (SELECT EnId From Entries) ";
	safe_w_sql($Sql);
// 	$Sql = "DELETE FROM Eliminations WHERE ElId NOT IN (SELECT EnId From Entries) ";
// 	safe_w_sql($Sql);
	$Sql = "DELETE FROM Individuals WHERE IndId NOT IN (SELECT EnId From Entries) ";
	safe_w_sql($Sql);
	$Sql = "DELETE FROM Photos WHERE PhEnId NOT IN (SELECT EnId From Entries) ";
	safe_w_sql($Sql);
	$Sql = "DELETE FROM Vegas WHERE VeId NOT IN (SELECT EnId From Entries) ";
	safe_w_sql($Sql);
	$Sql = "DELETE FROM ExtraData WHERE EdId NOT IN (SELECT EnId From Entries) ";
	safe_w_sql($Sql);
	$Sql = "DELETE FROM ExtraDataCountries WHERE EdcId NOT IN (SELECT CoId From Countries) ";
	safe_w_sql($Sql);
    $Sql = "UPDATE IskDevices SET IskDvTournament=0 WHERE IskDvTournament = " . StrSafe_DB($TourId);
    safe_w_sql($Sql);
	// removea all media
	RemoveMedia($TourCode);

}

function tour_getCode($filename, $isString=false) {
    if($isString) {
        $Gara=unserialize(gzuncompress($filename));
    } else {
        $Gara=unserialize(gzuncompress(file_get_contents($filename)));
    }
    return $Gara['Tournament']['ToCode'];
}

function tour_import($filename, $isString=false) {
	require_once('Common/CheckPictures.php');
	// Tabelle che hanno il codice Tournament
	$tabs_on_tour=array(
		'AccColors' => 'Ac',
		'AccEntries' => 'AE',
		'AccPrice' => 'AP',
		'ACL' => 'Acl',
		'AclDetails' => 'AclDt',
        'AclTemplates' => 'AclTe',
		'AvailableTarget' => 'At',
		'Awards' => 'Aw',
		'Awarded' => 'Aw',
		'BackNumber' => 'Bn',
		'CasScore' => 'CaS',
		'CasTeam' => 'Ca',
		'CasTeamFinal' => 'CTF',
		'CasTeamTarget' => 'CTT',
		'Classes' => 'Cl',
		'ClassWaEquivalents' => 'ClWaEq',
		'ClubTeam' => 'CT',
		'ClubTeamScore' => 'CTS',
		'Countries' => 'Co',
		'Divisions' => 'Div',
		'DistanceInformation' => 'Di',
		'DocumentVersions' => 'Dv',
		'Emails' => 'Em',
		'Entries' => 'En',
		'EventClass' => 'Ec',
		'Events' => 'Ev',
		'Individuals' => 'Ind',
		'FinalReportA' => 'Fra',
		'Finals' => 'Fin',
		'FinOdfTiming' => 'FinOdf',
		'FinSchedule' => 'FS',
		'FinWarmup' => 'Fw',
		//'Flags' => 'Fl',
        'GateLog' => 'GL',
        'HeartBeat'=>'Hb',
		'HhtData' => 'Hd',
		'HhtEvents' => 'He',
		'HhtSetup' => 'Hs',
		'IdCardElements' => 'Ice',
		'IdCards' => 'Ic',
		'Images' => 'Im',
        'IskData' => 'IskDt',
		'Logs' => 'Log',
		'ModulesParameters' => 'Mp',
// 		'OnLineIds' => 'Oli', // this table gets deleted/inserted if local TourId changes so no need to import/export it
		'OdfDocuments' => 'OdfDoc',
		'OdfTranslations' => 'OdfTr',
		'OdfMessageStatus' => 'Oms',
		'Rankings' => 'Rank',
		'RecBroken' => 'RecBro',
		'RecTournament' => 'Rt',
		'Reviews' => 'Rev',
		"RoundRobinGrids" => "RrGrid",
		"RoundRobinGroup" => "RrGr",
		"RoundRobinLevel" => "RrLev",
		"RoundRobinMatches" => "RrMatch",
		"RoundRobinParticipants" => "RrPart",
		'RunArchery' => 'Ra',
		'RunArcheryRank' => 'Rar',
		'RunArcheryParticipants' => 'Rap',
		'SubClass' => 'Sc',
		'TargetFaces' => 'Tf',
		'TargetGroups' => 'Tg',
		'TeamComponent' => 'Tc',
		'TeamFinals' => 'Tf',
		'TeamDavis' => 'TeDa',
		'TeamFinComponent' => 'Tfc',
        'TeamFinComponentLog' => 'Tfcl',
		'Teams' => 'Te',
		'TournamentDistances' => 'Td',
		'TournamentInvolved' => 'Ti',
		'TourRecords' => 'Tr',
		'TVContents' => 'TVC',
		'TVParams' => 'TVP',
		'TVRules' => 'TVR',
		'TVSequence' => 'TVS',
		'Session'=>'Ses',
		'Scheduler'=>'Sch',
		'Eliminations'=>'El',
		'VegasAwards'=>'Va',
	);

	// Tabelle che hanno il codice Countries
	$tab_to_country=array(
		'CasTeam' => 'CaTeam',
		'ClubTeam' => 'CTTeam',
		'Entries' => array('EnCountry','EnCountry2','EnCountry3'),
		'ExtraDataCountries' => 'EdcId',
		'RecBroken' => 'RecBroTeam',
		'TeamComponent' => 'TcCoId',
		'TeamFinals' => 'TfTeam',
		'TeamFinComponent' => 'TfcCoId',
        'TeamFinComponentLog' => 'TfclCoId',
		'Teams' => 'TeCoId',
		'TournamentInvolved' => 'TiCountry',
		);

	// Tabelle che hanno il codice Entries
	$tab_to_entry=array(
		'AccEntries' => 'AEId',
		'Awarded' => 'AwEntry',
		'ElabQualifications' => 'EqId',
		'Eliminations' => 'ElId',
		'ExtraData' => 'EdId',
		'Finals' => array('FinAthlete','FinCoach'),
        'GateLog' => 'GLEntry',
		'HhtData' => 'HdEnId',
		'Individuals' => 'IndId',
		'Logs' => 'LogEntry',
		'Photos' => 'PhEnId',
		'Qualifications' => 'QuId',
		'RecBroken' => 'RecBroAthlete',
		'RunArchery' => 'RaArcher',
        'TeamFinals' => 'TfCoach',
		'TeamComponent' => 'TcId',
		'TeamFinComponent' => 'TfcId',
        'TeamFinComponentLog' => array('TfclIdPrev','TfclIdNext'),
		'Vegas' => 'VeId',
		);

    // Tabelle che hanno il codice Entries in un array
    $tab_to_entryarray=array(
        'TeamFinals' => 'TfShootingArchers'
    );

    // Tabelle che hanno il codice entriesInvolved
    $tab_to_tourinvolved=array(
        'FinSchedule' => array('FsLJudge','FsTJudge'),
    );

	// Tabelle che dipendono SOLO da Tournament
	$tabs_only_tour=array(
		'AccColors',
		'AccPrice',
		'ACL',
        'AclTemplates',
		'AclDetails',
		'AvailableTarget',
		'Awards',
		'BackNumber',
		'CasScore',
		'CasTeamFinal',
		'CasTeamTarget',
		'Classes',
		'ClassWaEquivalents',
		'ClubTeamScore',
		'DistanceInformation',
		'DocumentVersions',
		'Divisions',
		'Emails',
		'EventClass',
		'Events',
		'FinalReportA',
		'FinOdfTiming',
		'FinWarmup',
		//'Flags',
        'HeartBeat',
		'HhtEvents',
		'HhtSetup',
		'IdCardElements',
		'IdCards',
        'IskData',
		'Images',
		'ModulesParameters',
// 		'OnLineIds', see comments above
		'OdfDocuments',
		'OdfTranslations',
		'OdfMessageStatus',
		'Rankings',
		'RecTournament' ,
		'Reviews',
		"RoundRobinGrids",
		"RoundRobinGroup",
		"RoundRobinLevel",
		'SubClass',
		'TargetFaces',
		'TargetGroups',
		'TeamDavis',
		'TournamentDistances',
		'TourRecords' ,
		'TVContents',
		'TVParams',
		'TVRules',
		'TVSequence',
		'Session',
		'Scheduler',
		'Eliminations',
		'VegasAwards',
	);

	$RoundRobins=[
		"RoundRobinMatches" => ['RrMatchTeam', 'RrMatchAthlete'],
		"RoundRobinParticipants" => ['RrPartTeam', 'RrPartParticipant'],
		"RunArchery" => ['RaTeam', 'RaEntry'],
		"RunArcheryRank" => ['RarTeam', 'RarEntry'],
		"RunArcheryParticipants" => ['RapTeamEvent', 'RapEntry'],
	];

	if($isString) {
		$Gara=unserialize(gzuncompress($filename));
	} else {
		$Gara=unserialize(gzuncompress(file_get_contents($filename)));
	}

	// If is not compatible, exits
	if($Gara['Tournament']['ToDbVersion'] > GetParameter('DBUpdate')) {
		return false;
	}

	require_once('UpdateTournament.inc.php');
	$Gara=UpdateTournament($Gara);

	// CONTROLLA SE C'E' UN TORNEO CON LO STESSO CODICE E LO SEGA!
	$q=safe_r_sql("select `ToId` from `Tournament` where `ToCode`=" . strsafe_db($Gara['Tournament']['ToCode']));
	if($r=safe_fetch($q) ){
		// esiste un tournament con lo stesso codice... ranzo tutto!
		tour_delete($r->ToId);
	}

	// elimina i media di questa gara
	RemoveMedia($Gara['Tournament']['ToCode']);

	// Insert competition
    $query=array();
	foreach($Gara['Tournament'] as $key=>$val) {
		if($key!='ToId'){
            if(!is_null($val)) {
                $query[] = "`$key` = " . strsafe_db($val);
            } else {
                $query[] = "`$key` = NULL";
            }
		}
	}
	safe_w_sql("Insert into `Tournament` set ".implode(', ', $query));
	$TourId=safe_w_last_id();

	// adjust ToId in all arrays...
	foreach($tabs_on_tour as $tab=>$code) {
		if(isset($Gara[$tab])) {
			foreach($Gara[$tab] as $key=>$val) {
				$Gara[$tab][$key][$code.'Tournament']=$TourId;
			}
		}
	}

	// inserisce le tabelle che hanno SOLO il tournament
	foreach($tabs_only_tour as $tab) {
		if(isset($Gara[$tab])) {
			foreach($Gara[$tab] as $record) {
				$query=array();
				foreach($record as $key=>$val) {
                    if(!is_null($val)) {
                        $query[] = "`$key` = " . strsafe_db($val);
                    } else {
                        $query[] = "`$key` = NULL";
                    }
				}
				safe_w_sql("insert into `$tab` set ". implode(', ', $query). " on duplicate key update ". implode(', ', $query));
			}
		}
	}

	// inserisce i paesi e mantieni l'array per il cambio country
	$Countries=array();
	if(array_key_exists('Countries',$Gara) && is_array($Gara['Countries']) && count($Gara['Countries'])>0) {
		foreach($Gara['Countries'] as $record) {
			$query=array();
			foreach($record as $key=>$val) {
				if($key!='CoId' and $key!='CoFlag' and $key!='CoMail' and $key!='CoNoPrint'){
                    if(!is_null($val)) {
                        $query[] = "`$key` = " . strsafe_db($val);
                    } else {
                        $query[] = "`$key` = NULL";
                    }
				}
			}
			safe_w_sql("insert into `Countries` set ". implode(', ', $query));
			$Countries[$record['CoId']]=safe_w_last_id();
		}
	}
	//aggiorna CoParent1 della Countries stessa
	$tmpSql = "SELECT DISTINCT `CoParent1` FROM `Countries` WHERE `CoTournament`=" . $TourId . " AND CoParent1!=0";
	$tmpRs = safe_r_sql($tmpSql);
	if(safe_num_rows($tmpRs)!=0) {
		while($tmpRow=safe_fetch($tmpRs))
			safe_w_sql("UPDATE `Countries` SET `CoParent1`=". $Countries[$tmpRow->CoParent1] . " WHERE `CoParent1`=" . $tmpRow->CoParent1 . " AND `CoTournament`=" . $TourId);
		safe_free_result($tmpRs);
	}
	//aggiorna CoParent2 della Countries stessa
	$tmpSql = "SELECT DISTINCT `CoParent2` FROM `Countries` WHERE `CoTournament`=" . $TourId . " AND CoParent2!=0";
	$tmpRs = safe_r_sql($tmpSql);
	if(safe_num_rows($tmpRs)!=0) {
		while($tmpRow=safe_fetch($tmpRs))
		safe_w_sql("UPDATE `Countries` SET `CoParent2`=". $Countries[$tmpRow->CoParent2] . " WHERE `CoParent2`=" . $tmpRow->CoParent2 . " AND `CoTournament`=" . $TourId);
		safe_free_result($tmpRs);
	}

	// aggiorna i paesi nelle tabelle che ne fanno uso
	foreach($tab_to_country as $tab=>$field) {
		if(array_key_exists($tab, $Gara)) {
			foreach($Gara[$tab] as $key=>$record) {
				if(is_array($field)) {
					foreach($field as $ff) {
						if(array_key_exists($ff,$record) && $record[$ff])
							$Gara[$tab][$key][$ff]=$Countries[$record[$ff]];
					}
				} else {
				    if(array_key_exists($field,$record) AND $record[$field] AND array_key_exists($record[$field],$Countries))
						$Gara[$tab][$key][$field]=$Countries[$record[$field]];
				}
			}
		}
	}

    // inserisce le TournamentInvolved e mantieni l'array per il cambio TiId
    $TiEntries=array();
    if(array_key_exists('TournamentInvolved',$Gara) && is_array($Gara['TournamentInvolved']) && count($Gara['TournamentInvolved'])>0) {
        foreach($Gara['TournamentInvolved'] as $record) {
            if(isset($record['TiId'])) {
                $query = array();
                foreach ($record as $key => $val) {
                    if ($key != 'TiId') {
                        if(!is_null($val)) {
                            $query[] = "`$key` = " . strsafe_db($val);
                        } else {
                            $query[] = "`$key` = NULL";
                        }
                    }
                }
                safe_w_sql("insert into `TournamentInvolved` set " . implode(', ', $query));
                $TiEntries[$record['TiId']] = safe_w_last_id();
            }
        }
    }
    // aggiorna le rimanenti tabelle con le Entries corrette
    if(count($TiEntries)) {
        foreach ($tab_to_tourinvolved as $tab => $field) {
            if (array_key_exists($tab, $Gara)) {
                foreach ($Gara[$tab] as $key => $record) {
                    if (is_array($field)) {
                        foreach ($field as $ff) {
                            if (array_key_exists($ff, $record) && $record[$ff])
                                $Gara[$tab][$key][$ff] = $TiEntries[$record[$ff]];
                        }
                    } else {
                        if (array_key_exists($field, $record) and $record[$field] and array_key_exists($record[$field], $Entries))
                            $Gara[$tab][$key][$field] = $TiEntries[$record[$field]];
                    }
                }
            }
        }
    }

	// inserisce le Entries e mantieni l'array per il cambio Entry
	$Entries=array();
	if(array_key_exists('Entries',$Gara) && is_array($Gara['Entries']) && count($Gara['Entries'])>0) {
		foreach($Gara['Entries'] as $record) {
			$query=array();
			foreach($record as $key=>$val) {
				if($key!='EnId' and $key!='EnWorldRank'){
                    if(!is_null($val)) {
                        $query[] = "`$key` = " . strsafe_db($val);
                    } else {
                        $query[] = "`$key` = NULL";
                    }
				}
			}
			safe_w_sql("insert into `Entries` set ". implode(', ', $query));
			$Entries[$record['EnId']]=safe_w_last_id();
		}
	}
	// aggiorna le rimanenti tabelle con le Entries corrette
	foreach($tab_to_entry as $tab=>$field) {
		if(array_key_exists($tab, $Gara)) {
            foreach($Gara[$tab] as $key=>$record) {
                if(is_array($field)) {
                    foreach($field as $ff) {
                        if(array_key_exists($ff,$record) && $record[$ff]) {
                            $Gara[$tab][$key][$ff] = $Entries[$record[$ff]];
                        }
                    }
                } else {
                    if(array_key_exists($field,$record) AND $record[$field] AND array_key_exists($record[$field],$Entries)) {
                        $Gara[$tab][$key][$field] = $Entries[$record[$field]];
                    }
                }
            }
		}
	}
    //Update TeamFinals to update the shooter order
    foreach ($tab_to_entryarray as $tab => $field) {
        if (array_key_exists($tab, $Gara)) {
            foreach ($Gara[$tab] as $key => $record) {
                if(array_key_exists($field,$record) AND $record[$field] != '' AND $tmpArchers = json_decode($record[$field], true)) {
                    foreach ($tmpArchers as $kArc => $vArc) {
                        $tmpArchers[$kArc] = $Entries[$vArc] ?? 0;
                    }
                    $Gara[$tab][$key][$field] = (count($tmpArchers) ? json_encode($tmpArchers) : '');
                }
            }
        }
    }

	// updates the tables that have a mixed country/entry based on the "team" flag
	foreach($RoundRobins as $tab => $flags) {
		if(array_key_exists($tab, $Gara)) {
			foreach($Gara[$tab] as $key=>$record) {
				if(array_key_exists($flags[0], $record)) {
					if($record[$flags[0]]) {
						// team so gets the country
						if(array_key_exists($record[$flags[1]], $Countries)) {
							$Gara[$tab][$key][$flags[1]]=$Countries[$record[$flags[1]]];
						}
					} else {
						// Entries
						if(array_key_exists($record[$flags[1]], $Entries)) {
							$Gara[$tab][$key][$flags[1]]=$Entries[$record[$flags[1]]];
						}
					}
				}
			}
		}
	}

	// inserisce le tabelle restanti
	unset($tab_to_country['Entries']);
    unset($tab_to_country['TournamentInvolved']);
    $final_tabs = array_unique(array_merge(array_keys($tab_to_country), array_keys($tab_to_entry), array_keys($tab_to_tourinvolved), array_keys($RoundRobins)));
	foreach($final_tabs as $tab) {
		if(array_key_exists($tab, $Gara)) {
			foreach($Gara[$tab] as $record) {
				$query=array();
				foreach($record as $key=>$val) {
                    if(!is_null($val)) {
                        $query[] = "`$key` = " . strsafe_db($val);
                    } else {
                        $query[] = "`$key` = NULL";
                    }
				}

				// attenzione: questa query era "commentata" per non far apparirer errori:
				// se questo comportamento è desiderato, chiamare la funzione safe_w_sql passando
				// come terzo parametro un array con gli errori numerici ammessi
				safe_w_sql("REPLACE INTO `$tab` set ". implode(', ', $query));
			}
		}
	}

	//Gestisce la tabella Flags
	if(array_key_exists('Flags',$Gara) && is_array($Gara['Flags']) && count($Gara['Flags'])>0) {
		foreach($Gara['Flags'] as $record) {
			if($record['FlTournament']!=-1)
				$record['FlTournament']=$TourId;
			$query=array();
			foreach($record as $key=>$val) {
                if(!is_null($val)) {
                    $query[] = "`$key` = " . strsafe_db($val);
                } else {
                    $query[] = "`$key` = NULL";
                }
			}
			safe_w_sql("insert into `Flags` set ". implode(', ', $query). " on duplicate key update ". implode(', ', $query));
		}
	}

	// manages record areas not maintained by World Archery
	if(!empty($Gara['RecAreas'])) {
		foreach($Gara['RecAreas'] as $record) {
			$query=array();
			foreach($record as $key=>$val) {
                if(!is_null($val)) {
                    $query[] = "`$key` = " . strsafe_db($val);
                } else {
                    $query[] = "`$key` = NULL";
                }
			}
			safe_w_sql("insert into `RecAreas` set ". implode(', ', $query). " on duplicate key update ". implode(', ', $query));
		}
	}

// 	// Manage OnLineIds table
// 	foreach($Entries as $Org => $New) {
// 		safe_w_sql("update OnLineIds set OliId=$New where OliTournament=$TourId and OliType='E' and OliId=$Org");
// 	}
// 	foreach($Countries as $Org => $New) {
// 		safe_w_sql("update OnLineIds set OliId=$New where OliTournament=$TourId and OliType='C' and OliId=$Org");
// 	}
// 	safe_w_sql("update OnLineIds set OliId=$TourId where OliTournament=$TourId and OliType='T'");

	// if for accreditation, inserts all the LocCodes!
	if(!empty($_REQUEST['Accreditation'])) {
		safe_w_sql("insert ignore into `ExtraData` (select `EnId`, 'Z', '', concat(`EnCode`, '-', `EnIocCode`, '-', `EnDivision`) from `Entries` where `EnTournament`=$TourId)");
	}

	// RECREATES ALL MEDIA
	CheckPictures($Gara['Tournament']['ToCode']);

	// check if this is an active Accreditation Booth
	if(GetParameter('AccBoothActive') and in_array($Gara['Tournament']['ToCode'], explode(', ', GetParameter('AccBoothCodes')))) {
		require_once('Modules/AccreditationBooth/Lib.php');
		// delete all the log files of this competition...
		deleteAccLogs($Gara['Tournament']['ToCode']);
		// track info for this competition in all servers ready to track
		$q=safe_r_sql("select distinct `SsApi`, `SsName` from `ianseo_Accreditation`.`SyncServers` where SsStatus=1");
		while ($r=safe_fetch($q)) {
			startAccTrack($Gara['Tournament']['ToCode'], $r->SsName, $r->SsApi);
		}
	}

	// adjust the ODF status
	checkOdfSystem($TourId, $Gara['Tournament']['ToCode']);

	return ($TourId);
}

function RemoveOdf($TourId, $TourCode) {
	if($OdfCode=getModuleParameter('ODF', 'CompCode', '', $TourId)) {
		if($tmpOdf=GetParameter('Odf-'.$OdfCode.'-T', false, '', true)) {
			unset($tmpOdf[$TourCode]);
			if(count($tmpOdf)==0) {
				DelParameter('Odf-'.$OdfCode.'-T');
			} else {
				SetParameter('Odf-'.$OdfCode.'-T', $tmpOdf, true);
			}
		}
		if($tmpOdf=GetParameter('Odf-'.$OdfCode.'-P', false, '', true)) {
			unset($tmpOdf[$TourCode]);
			if(count($tmpOdf)==0) {
				DelParameter('Odf-'.$OdfCode.'-P');
			} else {
				SetParameter('Odf-'.$OdfCode.'-P', $tmpOdf, true);
			}
		}
	}
}

function checkOdfSystem($TourId, $TourCode) {
	if($OdfCode=getModuleParameter('ODF', 'CompCode', '', $TourId) and $StationType=getModuleParameter('ODF', 'StationType', '', $TourId)) {
		if($StationType=='R') {
			$ProdType=getModuleParameter('ODF', 'TestMode', 'T', $TourId);
			// add this into the competition parameter
			$tmpOdf=GetParameter('Odf-'.$OdfCode.'-'.$ProdType, false, array(), true);
			$tmpOdf[$TourCode]=1;
			SetParameter('Odf-'.$OdfCode.'-'.$ProdType, $tmpOdf, true);
		}
	}
}
