<?php

/**
 *
 * I codici dei file sono:
 * IMG --> le immagini della gara
 * ENS --> Start list per piazzola
 * ENC --> Start list per società
 * ENA --> Start list per ordine alfabetico
 * IC --> Classifica di classe individuale
 * TC --> Classifica di classe a squadre
 * IQ(evento) --> Qualificazione individuale dell'evento (evento)
 * TQ(evento) --> Qualificazione a squadre dell'evento (evento)
 * IE(evento) --> Eliminatorie individuali dell'evento (evento)
 * IF(evento) --> Finale individuale dell'evento (evento) (Rank)
 * TF(evento) --> Finale a squadre dell'evento	(Rank)
 * IB(evento) --> Finale individuale dell'evento (evento) (Bracket)
 * TB(evento) --> Finale a squadre dell'evento	(evento) (Bracket)
 *
 * MEDSTD --> Medal standing
 * MEDLST --> Medal list
*/

$JSON=array('error' => 1, 'msg' => 'Generic Error');
require_once(dirname(dirname(__FILE__)) . '/config.php');
if(isset($_REQUEST["ToCode"]) AND $ToId=getIdFromCode($_REQUEST["ToCode"])) {
    CreateTourSession($ToId);
    $Credentials=getModuleParameter('SendToIanseo', 'Credentials', (object)array('OnlineId' => 0, 'OnlineAuth' => ''));
    if($Credentials and $Credentials->OnlineId>0) {
        require_once('Common/Lib/CommonLib.php');
        if($ErrorMessage=CheckCredentials($Credentials->OnlineId, $Credentials->OnlineAuth, 'Tournament/'.basename(__FILE__))) {
            JsonOut($JSON);
        }
    } else {
        JsonOut($JSON);
    }

    $_REQUEST['oris']=$_SESSION['ISORIS'];
    $q=safe_r_SQL("SELECT * FROM `TourRecords` WHERE `TrTournament` = " . StrSafe_DB($_SESSION['TourId']));
    $_REQUEST['showRecords'] = (safe_num_rows($q) > 0);
}

if(!CheckTourSession() or checkACL(AclInternetPublish, AclReadWrite, false)!=AclReadWrite or IsBlocked(BIT_BLOCK_PUBBLICATION)) {
    JsonOut($JSON);
}

require_once('Qualification/Fun_Qualification.local.inc.php');
require_once('Common/Lib/Fun_Phases.inc.php');
require_once('Common/OrisFunctions.php');

$URL=$CFG->IanseoServer.'Upload-Competition.php';

if(empty($_SESSION['OnlineId']) or empty($_SESSION['OnlineAuth']) or empty($_SESSION['OnlineServices']) or !($_SESSION['OnlineServices']&1) or empty($_SESSION['OnlineEventCode'])) {
    $JSON['msg']='Missing online accreditation!';
    JsonOut($JSON);
}

$IsRunArchery=($_SESSION['TourType']==48);

$RET=new StdClass();

// WE ONLY SEND ORIS STUFF
$ORIS=!empty($_REQUEST['oris']);

if(!defined('PRINTLANG')) {
    if($ORIS) {
        define('PRINTLANG', 'EN');
    } else {
        define('PRINTLANG', $_SESSION['TourPrintLang']);
    }
}

$RET->ORIS = $ORIS;
$RET->OnlineId = $_SESSION['OnlineId'];
$RET->OnlineAuth = $_SESSION['OnlineAuth'];
$RET->OnlineEventCode = $_SESSION['OnlineEventCode'];
$RET->lastUpload = date('Y-m-d H:i:s');
$RET->UUID=GetParameter('UUID2', false, uniqid('Ianseo-', true));
$RET->ProgVersion = ProgramVersion;
$RET->ProgRelease = ProgramRelease;
$RET->ProgBuild = ProgramBuild;
$RET->IsRunArchery = ($_SESSION['TourType']==48);

$RET->PDF=array();
$RET->FilRemove=array();
$RET->FilRename=array();
$RET->URL=array();
$RET->UrlRemove=array();
$RET->UrlRename=array();
$ShowRecords=isset($_REQUEST['showRecords']);

if(empty($_REQUEST['btnDelOnline'])) {
    // Deal with PDFS
    //if(!empty($_REQUEST['ScoQual'])) $RET->PDF[]=getScoQuals();
    //if(!empty($_REQUEST['ScoElim'])) $RET->PDF[]=getScoElim();
    //if(!empty($_REQUEST['ScoBra'])) $RET->PDF[]=getScoInd();
    //if(!empty($_REQUEST['ScoBraTeam'])) $RET->PDF[]=getScoTeams();
    if(!empty($_REQUEST['FOP'])) $RET->PDF[]=getFop($_REQUEST['FOPorder'], $_REQUEST['FOPname']);
    if(!empty($_REQUEST['SCH'])) $RET->PDF[]=getSchedule($_REQUEST['SCHorder'], $_REQUEST['SCHname']);

    if(!empty($_FILES['FIL']) and !empty($_FILES['FIL']['size']) and empty($_FILES['FIL']['error']) and $_FILES['FIL']['type']=='application/pdf') {
    	$RET->PDF[]=getGenericPdf($_FILES['FIL'], $_REQUEST['FILname'], $_REQUEST['FILorder']);
    }
    // uploaded files
    if(!empty($_REQUEST['FilesRemove'])) {
        $RET->FilRemove=$_REQUEST['FilesRemove'];
    }
    if(!empty($_REQUEST['FilesDescr'])) {
	    foreach($_REQUEST['FilesDescr'] as $fname => $description) {
	        if(empty($_REQUEST['FilesRemove']) or !in_array($fname, $_REQUEST['FilesRemove'])) {
				$RET->FilRename[$fname]=array(
					'order' => intval($_REQUEST['FilesOrder'][$fname]),
					'descr' => $description,
					);
		    }
	    }
    }

    if(!empty($_REQUEST['URL'])) {
    	$RET->URL= array(
    		'order' => $_REQUEST['URLorder'],
    		'descr' => $_REQUEST['URLname'],
    		'url' => $_REQUEST['URL'],
	    );
    }
    // uploaded URLS
    if(!empty($_REQUEST['UrlsRemove'])) {
        $RET->UrlRemove=$_REQUEST['UrlsRemove'];
    }
    if(!empty($_REQUEST['UrlsDescr'])) {
	    foreach($_REQUEST['UrlsDescr'] as $urlId => $description) {
	        if(empty($_REQUEST['UrlsRemove']) or !in_array($fname, $_REQUEST['UrlsRemove'])) {
				$RET->UrlRename[$urlId]=array(
		            'order' => $_REQUEST['UrlsOrder'][$urlId],
		            'descr' => $description,
		            'url' => $_REQUEST['UrlsUrl'][$urlId],
				);
		    }
	    }
    }


    // send all the header stuff with images etc...
    if(!empty($_REQUEST['IMG']) or $_SESSION['SendOnlinePDFImages']) $RET->IMG=getPdfHeader();

    // Entire Book
    $RET->BOOK=(!empty($_REQUEST['BOOK']));

    // List by targets
    if(!empty($_REQUEST['ENS'])) {
		if($IsRunArchery) {
			$RET->ENS=getRunStartListSession($ORIS);
		} else {
			$RET->ENS=getStartList($ORIS);
		}
    }

    // List by category
    if(!empty($_REQUEST['ENE'])) {
		if($IsRunArchery) {
			$RET->ENE=getRunStartListSession($ORIS, '', 'Event');
		} else {
			$RET->ENE=getStartListCategory($ORIS, 1);
		}
    }

    // List by Countries
    if(!empty($_REQUEST['ENC'])) {
		if($IsRunArchery) {
			$RET->ENC=getRunEntries($ORIS, '', 'Country');
		} else {
			$RET->ENC=getStartListByCountries($ORIS);
		}
    }

    // List by Entries
    if(!empty($_REQUEST['ENA'])) {
		if($IsRunArchery) {
			$RET->ENA=getRunEntries($ORIS, '', 'Alpha');
		} else {
			$RET->ENA=getStartListAlphabetical($ORIS, true);
		}
    }

    // Stats by Countries
    if(!empty($_REQUEST['STC'])) $RET->STC=getStatEntriesByCountries($ORIS);

    // Stats by Entries
    if(!empty($_REQUEST['STE'])) $RET->STE=getStatEntriesByEvent($ORIS);

    // Officials on Field
    if(!empty($_REQUEST['STF'])) $RET->STF=getCompetitionOfficials(true);

    /** DIVCLASS RANKING */
    // Ranking by Category, Individual (local rules apply)
    if(!empty($_REQUEST['IC'])) $RET->IC=getDivClasIndividual('', '', ($_SESSION['TourType']==14 or $_SESSION['TourType']==32) ? array('SubClassRank' => '1') : array());

    /** DIVCLASS RANKING */
    // Ranking by Category, Teams (local rules apply)
    if(!empty($_REQUEST['TC'])) $RET->TC=getDivClasTeam();

    // Qualification, Individual
    if(!empty($_REQUEST['QualificationInd'])) {
        $RET->IQ=new StdClass();
        foreach($_REQUEST['QualificationInd'] as $Event) $RET->IQ->{$Event} = getQualificationIndividual(substr($Event,2), $ORIS, $ShowRecords);
    }

    // Elimination, Startlist
    if(!empty($_REQUEST['EliminationStartlist'])) {
        $RET->EL=new StdClass();
        foreach($_REQUEST['EliminationStartlist'] as $Event) {
            $IsPool=substr($Event, -1);
            $RET->EL->{$Event}=getStartList($ORIS, $IsPool>2 ? '' : substr($Event, -1), true, false, $IsPool>2 ? $IsPool : false,true);
        }
    }

    // Elimination, Individual
    if(!empty($_REQUEST['EliminationInd'])) {
        $RET->IE=new StdClass();
        $RET->IP=new StdClass();
        foreach($_REQUEST['EliminationInd'] as $Event) {
            if(substr($Event, 0 ,2)=='IP') {
                $isPool=substr($Event, -1);
                $RET->IP->{$Event}=getEliminationPoolIndividual(substr($Event,2, -1), true, $isPool);
                if(empty($RET->IP->{$Event}->Data['Items'])) {
                	unset($RET->IP->{$Event});
                }
            } else {
                $RET->IE->{$Event}=getEliminationIndividual(substr($Event,2),$ORIS);
            }
        }
    }

    // Robin, Startlist
    // if(!empty($_REQUEST['RobinStartlist'])) {
    //     $RET->RL=new StdClass();
    //     foreach($_REQUEST['RobinStartlist'] as $Event) {
    //         $RET->RL->{$Event}=getStartList($ORIS, $IsPool>2 ? '' : substr($Event, -1), true, false, $IsPool>2 ? $IsPool : false,true);
    //     }
    // }

    // Robin, Individual
    if(!empty($_REQUEST['RobinInd'])) {
        $RET->IR=new StdClass();
        foreach($_REQUEST['RobinInd'] as $Event) {
            $RET->IR->{$Event}=getRobin(['team'=>substr($Event,1,1), 'events'=>[substr($Event,2)]],$ORIS);
        }
    }

    // Robin, Team
    if(!empty($_REQUEST['RobinTeam'])) {
        $RET->IR=new StdClass();
        foreach($_REQUEST['RobinTeam'] as $Event) {
            $RET->IR->{$Event}=getRobin(['team'=>substr($Event,1,1), 'events'=>[substr($Event,2)], 'includeTeamRank' => $_SESSION['TourLocSubRule']=='SetFRD12023'],$ORIS);
        }
    }

    // Qualification, Team
    if(!empty($_REQUEST['QualificationTeam'])) {
        $RET->TQ=new StdClass();
        foreach($_REQUEST['QualificationTeam'] as $Event) $RET->TQ->{$Event}=getQualificationTeam(substr($Event,2),$ORIS, $ShowRecords);
    }

    // Brackets, Individual
    if(!empty($_REQUEST['BracketsInd'])) {
        $RET->IB=new StdClass();
        foreach($_REQUEST['BracketsInd'] as $Event) {
            $EventCode = getChildrenEvents(substr($Event,2));
            $RET->IB->{$Event}=getBracketsIndividual($EventCode, $ORIS, true, true, true, $ShowRecords);
        }
    }

    // Brackets, Team
    if(!empty($_REQUEST['BracketsTeam'])) {
        $RET->TB=new StdClass();
        foreach($_REQUEST['BracketsTeam'] as $Event) {
            $EventCode = getChildrenEvents(substr($Event,2), 1);
            $RET->TB->{$Event}=getBracketsTeams($EventCode, $ORIS, true, true, true, $ShowRecords, null, true);
        }
    }

    // Final Rank, Individual
    if(!empty($_REQUEST['FinalInd'])) {
        $RET->IF=new StdClass();
        foreach($_REQUEST['FinalInd'] as $Event) {
			if($IsRunArchery) {
				$RET->IF->{$Event}=getRankingRunIndividual([substr($Event,2)], '', true);
			} else {
				$RET->IF->{$Event}=getRankingIndividual(substr($Event,2), $ORIS);
			}
        }
    }

    // Final Rank, Team
    if(!empty($_REQUEST['FinalTeam'])) {
        $RET->TF=new StdClass();
        if($ORIS) {
            $RET->TFC = new StdClass();
        }
        foreach($_REQUEST['FinalTeam'] as $Event) {
	        if($IsRunArchery) {
		        $RET->TF->{$Event}=getRankingRunTeams([substr($Event,2)], '');
	        } else {
				$RET->TF->{$Event}=getRankingTeams(substr($Event,2),$ORIS);
                if($ORIS) {
                    $RET->TFC->{$Event} = getTeamsComponentsLog(substr($Event, 2));
                }
	        }
        }
    }

    // Medal standing
    if(!empty($_REQUEST['MEDSTD'])) {
		$RET->MEDSTD=getMedalStand($ORIS);
    }

    // Medallists
    if(!empty($_REQUEST['MEDLST'])) {
		$RET->MEDLST=getMedalList($ORIS);
    }

    // Standing Record

    if(!empty($_REQUEST['RECSTD'])) {
        $q=safe_r_sql("SELECT count(*) as Involved FROM TourRecords WHERE TrTournament={$_SESSION['TourId']}");
        if($r=safe_fetch($q) and $r->Involved) {
            $RET->RECSTD = getStandingRecords(true);
        }
    }

    // Record Broken
    if(!empty($_REQUEST['RECBRK'])) {
        $q=safe_r_sql("SELECT count(*) as Involved FROM RecBroken WHERE RecBroTournament={$_SESSION['TourId']}");
        if($r=safe_fetch($q) and $r->Involved) {
            $RET->RECBRK = getBrokenRecords(true);
        }
    }
} else {
	// request to delete the selected items
    $RET->delete=array();

    if(!empty($_REQUEST['ENS'])) $RET->delete[]='ENS'; // List by Targets
    if(!empty($_REQUEST['ENE'])) $RET->delete[]='ENE'; // List by Events
    if(!empty($_REQUEST['ENC'])) $RET->delete[]='ENC'; // List by Countries
    if(!empty($_REQUEST['ENA'])) $RET->delete[]='ENA'; // List by Entries
    if(!empty($_REQUEST['STC'])) $RET->delete[]='STC'; // Stats of Countries
    if(!empty($_REQUEST['STE'])) $RET->delete[]='STE'; // Stats of Entries
    if(!empty($_REQUEST['IC'])) $RET->delete[]='IC'; // Ranking by Category, Individual (local rules apply)
    if(!empty($_REQUEST['TC'])) $RET->delete[]='TC'; // Ranking by Category, Teams (local rules apply)
    if(!empty($_REQUEST['QualificationInd'])) foreach($_REQUEST['QualificationInd'] as $Event) $RET->delete[]=''.$Event; // Qualification, Individual
    if(!empty($_REQUEST['EliminationInd'])) foreach($_REQUEST['EliminationInd'] as $Event) $RET->delete[]=''.$Event; // Elimination, Individual
    if(!empty($_REQUEST['QualificationTeam'])) foreach($_REQUEST['QualificationTeam'] as $Event) $RET->delete[]=''.$Event; // Qualification, Team
	if(!empty($_REQUEST['EliminationStartlist'])) foreach($_POST['EliminationStartlist'] as $Event) $RET->delete[]= ((substr($Event,-1,1)>='3')  ? 'IP-' : 'IE-').substr($Event,-1,1); // Elimination Startlist, Individual
	if(!empty($_REQUEST['BracketsInd'])) foreach($_REQUEST['BracketsInd'] as $Event) $RET->delete[]=''.$Event; // Brackets, Individual
    if(!empty($_REQUEST['BracketsTeam'])) foreach($_REQUEST['BracketsTeam'] as $Event) $RET->delete[]=''.$Event; // Brackets, Team
    if(!empty($_REQUEST['FinalInd'])) foreach($_REQUEST['FinalInd'] as $Event) $RET->delete[]=''.$Event; // Final Rank, Individual
    if(!empty($_REQUEST['FinalTeam'])) foreach($_REQUEST['FinalTeam'] as $Event) $RET->delete[]=''.$Event; // Final Rank, Team
    if(!empty($_REQUEST['RobinInd'])) foreach($_REQUEST['RobinInd'] as $Event) $RET->delete[]=''.$Event; // Round Robin, Individual
    if(!empty($_REQUEST['RobinTeam'])) foreach($_REQUEST['RobinTeam'] as $Event) $RET->delete[]=''.$Event; // Round Robin, Team
    if(!empty($_REQUEST['MEDSTD'])) $RET->delete[]='MEDSTD'; // Medal standing
    if(!empty($_REQUEST['MEDLST'])) $RET->delete[]='MEDLST'; // Medallists
    if(!empty($_REQUEST['RECSTD'])) $RET->delete[]='SRECSTD'; // Record Standing
    if(!empty($_REQUEST['RECBRK'])) $RET->delete[]='SRECBRK'; // Record Broken
    if(!empty($_REQUEST['STF'])) $RET->delete[]='STF'; // Record Broken
    if(!empty($_REQUEST['BOOK'])) $RET->delete[]='BOOK'; // Record Broken
}

$postdata = http_build_query( array(
    "Tour" => gzcompress(serialize($RET)),
    "Version" => UploadVersion,
    ), '', '&' );

$opts = array('http' =>
    array(
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => $postdata
    )
);

$context = stream_context_create($opts);
$stream = fopen($URL, 'r', false, $context);
$tmp = null;

if($stream===false) {
    $tmpErr = error_get_last();
    $varResponse=array('<span style="color:#ff0000; font-size:large">ERROR: ' . $tmpErr["message"] . '</span>');
} else {
	$StreamAnswer=stream_get_contents($stream);
	$JSON['debug']=$StreamAnswer;
	if($Decoded=@json_decode($StreamAnswer, true)) {
		// all good... hopefully
		if($Decoded['error']==0) {
			$_SESSION['OnlineFiles']=$Decoded['files'];
			$_SESSION['OnlineUrls']=$Decoded['urls'];
			$JSON['files']=$Decoded['files'];
			$JSON['urls']=$Decoded['urls'];
            $JSON['error']=0;
			$JSON['msg']= '<span style="color:green">' . get_text('ERR_OK', 'Tournament') . '</span>' . '<br>' . date('H:i:s e');
		} else {
			$JSON['msg']=$Decoded['msg'];
		}
	} else {
		// something bad happened
	    $varResponse=explode('|', $StreamAnswer);
	    foreach($varResponse as $k => $v) {
	        if($v!='Tutto regolare' and $v!='ERR_OK') {
	            $varResponse[$k] = '<span style="color:red; font-size:large">' . get_text($v, 'Tournament', '', true) . '</span>';
	        } else {
	            $varResponse[$k] = '<span style="color:green">' . get_text($v, 'Tournament') . '</span>' . '<br>' . date('H:i:s e');
	            $JSON['error']=0;
	        }
	    }
		$JSON['msg']=implode('<br/>', $varResponse);
	}
}

$_SESSION['SendOnlinePDFImages']='';

jsonout($JSON);
