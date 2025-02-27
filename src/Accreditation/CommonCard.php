<?php

require_once('Common/Lib/Fun_Phases.inc.php');
$ExtraSql='';
$ExtraWhere='';
$ExtraGroup='GROUP BY EnId';

$QuSession='QuSession';
$QuSesLetter='Q';
$QuOrder='QuTargetNo';

$EventBased='';

$BibNumber=empty($_REQUEST['BibNumber']) ? '' : str_replace('UU', '_', $_REQUEST['BibNumber']);

if(empty($TourId)) {
    $TourId = $_SESSION['TourId'];
}

if(empty($SpecialFilter)) $SpecialFilter='';
if(empty($CardType)) $CardType='A';
if(empty($CardNumber)) $CardNumber=0;

if(empty($FIELDS)) {
	$FIELDS="EnId, EnCode as Bib, EnTournament, ToCode, ToName, ToWhere, ToWhenFrom, ToWhenTo, ToCategory,
		EnName AS Name, upper(EnFirstName) AS FirstName, EnName as GivCamel, upper(EnName) as GivCaps, EnFirstName as FamCamel, upper(EnFirstName) AS FamCaps,
		QuSession AS Session, SesName,
		c.CoId, c.CoCode AS NationCode, c.CoName AS Nation, upper(c.CoName) as NationCaps, 
		ifnull(c2.CoId,0) as CoId2, ifnull(c2.CoCode,'') AS NationCode2, ifnull(c2.CoName,'') AS Nation2, upper(ifnull(c2.CoName,'')) as NationCaps2,
		ifnull(c3.CoId,0) as CoId3, ifnull(c3.CoCode,'') AS NationCode3, ifnull(c3.CoName,'') AS Nation3, upper(ifnull(c3.CoName,'')) as NationCaps3,
		EnClass AS ClassCode, EnDivision AS DivCode, EnAgeClass as AgeClass, DivDescription,ClDescription,
		aextra.EdExtra as HasPlastic, aextra.EdEmail as HasPaper,
		cextra.EdExtra as EnCaption, COALESCE(RankRanking, '') as WRank, ifnull(pextra.EdExtra,0) as ExtraAddOns,
		EnSubClass as SubClass, EnStatus as Status, EnIndClEvent AS `IC`, EnTeamClEvent AS `TC`, EnIndFEvent AS `IF`, EnTeamFEvent as `TF`,
		AcColor, AcTitleReverse,(ClAthlete*DivAthlete) AS  AcIsAthlete, PhPhoto,
		AcArea0, AcArea1, AcArea2, AcArea3, AcArea4, AcArea5, AcArea6, AcArea7, AcAreaStar,
		AcTransport, AcAccomodation, AcMeal ";
}
$Where=array();

if(!empty($_REQUEST['Specifics'])) {
	$FIELDS.=", IcNumber";
} else {
	$FIELDS.=", $CardNumber as IcNumber";
}

switch($CardType) {
	case 'A': // Accreditation
		if($_SESSION['AccreditationTourIds']) $TourId=$_SESSION['AccreditationTourIds'];
		$FIELDS.=", (EnBadgePrinted is not null and EnBadgePrinted!=0) as Printed, '' ExtraCode, substr(QuTargetNo, 2) as TargetNo";
		if(!empty($_REQUEST['PrintNotPrinted']) and empty($BibNumber)) {
			// solo quelli non ancora stampati...
			// EnBadgePrinted contiene data e ora della stampa del badge...
			// quindi sono da recuperare quelli che hanno la data nulla oppure
			// oppure quelli la cui data di stampa è anteriore alla foto inserita
			$Where[] = ' AND (EnBadgePrinted is NULL or EnBadgePrinted=0 '.(empty($_REQUEST['PrintPhoto']) ? '' : 'or PhPhotoEntered is null  ').') ';
		}
		if(!empty($_REQUEST['HasPlastic'])) {
			$Where[] = " AND aextra.EdExtra='1' and aextra.EdEmail!='1' ";
		}
		if(empty($SORT)) $SORT='Printed, NationCode, FirstName, Name';
        $ExtraSql="LEFT JOIN Individuals ON IndTournament=EnTournament AND EnId=IndId
			LEFT JOIN Events on EvCode=IndEvent and EvTournament=EnTournament and EvTeamEvent=0
			";

		if(!empty($_REQUEST['Specifics'])) {
			$f=array();
			foreach($_REQUEST['Specifics'] as $ToId=>$specs) {
				foreach($specs as $k=>$v) {
					$f[]="(IcTournament=$ToId and IcNumber=$k and find_in_set(concat(EnDivision,EnClass), '$v'))";
				}
			}
			$ExtraSql.=" inner join IdCards on IcTournament=EnTournament and IcType='$CardType' and (".implode(' or ', $f).") ";
		}

		if(!empty($_REQUEST['TourId'])) {
			$Where[] = " AND EnTournament in (".implode(',', $_REQUEST['TourId']).") ";
		}
		break;
	case 'Q': // Qualifications
		$FIELDS.=", ToNumEnds as Ends, ToElabTeam, substr(QuTargetNo, 2)+0 as RealTarget, (QuBacknoPrinted is not null and QuBacknoPrinted!=0) as Printed, '' ExtraCode, substr(QuTargetNo, 2) as TargetNo";
		if(!empty($_REQUEST['PrintNotPrinted']) and empty($BibNumber)) {
			$Where[] = ' AND (QuBacknoPrinted is NULL or QuBacknoPrinted=0) ';
		}
		$Where[] = ' AND EnAthlete=1 ';
        $ExtraSql="LEFT JOIN Individuals ON IndTournament=EnTournament AND EnId=IndId
			LEFT JOIN Events on EvCode=IndEvent and EvTournament=EnTournament and EvTeamEvent=0
			";

        if(!empty($_REQUEST['Specifics'])) {
			$f=array();
			foreach($_REQUEST['Specifics'] as $ToId=>$specs) {
				foreach($specs as $k=>$v) {
					$f[]="(IcTournament=$ToId and IcNumber=$k and find_in_set(concat(EnDivision,EnClass), '$v'))";
				}
			}
			$ExtraSql.=" inner join IdCards on IcTournament=EnTournament and IcType='$CardType' and (".implode(' or ', $f).") ";
		}

		break;
	case 'E': // Eliminations
		$FIELDS.=", ElTargetNo+0 as RealTarget, if(ElElimPhase=0, EvE1Ends, EvE2Ends) as Ends, EvCode, EvEventName, IndRank as `Rank`, (ElBacknoPrinted is not null and ElBacknoPrinted!='0000-00-00 00:00:00') as Printed, concat(ElEventCode,ElElimPhase) ExtraCode, ElTargetNo as TargetNo";
		$ExtraSql="INNER JOIN Individuals ON IndTournament=EnTournament AND EnId=IndId
			INNER JOIN Events on EvCode=IndEvent and EvTournament=EnTournament and EvTeamEvent=0
			INNER JOIN Eliminations ON ElTournament=EnTournament AND ElEventCode=EvCode AND EnId=ElId
			";
		$ExtraWhere="";
		$ExtraGroup='Group By EnId, EvCode';
		if(empty($SORT)) $SORT='Printed, EvProgr, MatchNo';
		if(!empty($_REQUEST['PrintNotPrinted']) and empty($BibNumber)) {
			$Where[] = ' AND (ElBacknoPrinted is NULL or ElBacknoPrinted=0) ';
		}
		$QuSession='ElSession';
		$QuSesLetter='E';
		$QuOrder='ElTargetNo';

		if(!empty($_REQUEST['Specifics'])) {
			$f=array();
			foreach($_REQUEST['Specifics'] as $ToId=>$specs) {
				foreach ($specs as $k => $v) {
					$f[] = "(IcTournament=$ToId and IcNumber=$k and find_in_set(EvCode, '$v'))";
				}
			}
			$ExtraSql.=" inner join IdCards on IcTournament=EnTournament and IcType='$CardType' and (".implode(' or ', $f).") ";
		}

		break;
	case 'I': // Individual matches
		$FIELDS.=", EvCode, EvEventName, max(FinMatchNo) MatchNo, IndRank as `Rank`, EvCode ExtraCode, (IndBacknoPrinted is not null and IndBacknoPrinted!='0000-00-00 00:00:00') as Printed, '' as TargetNo ";
		$ExtraSql="INNER JOIN Individuals ON IndTournament=EnTournament AND EnId=IndId
			INNER JOIN Events ON EvCode=IndEvent AND EvTeamEvent=0 AND EvTournament=EnTournament
			INNER JOIN Finals ON FinEvent=EvCode AND FinAthlete=EnId AND FinTournament=EnTournament";
		$EventBased='EdEvent=EvCode';
		$ExtraWhere="";
		$ExtraGroup='Group By EnId, EvCode';
		if(empty($SORT)) $SORT='Printed, EvProgr, MatchNo';
		if(!empty($_REQUEST['PrintNotPrinted']) and empty($BibNumber)) {
			$Where[] = ' AND (IndBacknoPrinted is NULL or IndBacknoPrinted=0) ';
		}
		if(isset($_REQUEST['Phase']) and strlen($_REQUEST['Phase'])!=0 and $_REQUEST['Phase']!=-1) {
			$Phase=valueFirstPhase(intval($_REQUEST['Phase']));
			$ExtraSql.=' inner join Grids ON FinMatchNo=GrMAtchNo AND GrPhase='.$Phase;
		}

		if(!empty($_REQUEST['Specifics'])) {
			$f=array();
			foreach($_REQUEST['Specifics'] as $ToId=>$specs) {
				foreach($specs as $k=>$v) {
					$f[]="(IcTournament=$ToId and IcNumber=$k and find_in_set(EvCode, '$v'))";
				}
			}
			$ExtraSql.=" inner join IdCards on IcTournament=EnTournament and IcType='$CardType' and (".implode(' or ', $f).") ";
		}

		break;
	case 'T': // Team Matches
		$FIELDS.=", EvCode, TeamComponents, EvEventName, max(TfMatchNo) MatchNo, TeRank as `Rank`, EvCode ExtraCode, (TeBacknoPrinted is not null and TeBacknoPrinted!='0000-00-00 00:00:00') as Printed, '' as TargetNo ";
		$ExtraSql="INNER JOIN TeamFinComponent ON TfcId=EnId AND TfcTournament=EnTournament
			INNER join (select group_concat(concat_ws(' ', upper(EnFirstName), EnName) order by TfcOrder separator '|') TeamComponents, TfcCoId TeCoCoId, TfcSubTeam TeCoSubTeam, TfcEvent TeCoEvent from TeamFinComponent inner join Entries on EnId=TfcId and EnTournament=TfcTournament where TfcTournament in ($TourId) group by TfcTournament, TfcEvent, TfcCoId, TfcSubTeam) TC on TeCoCoId=TfcCoId and TeCoSubTeam=TfcSubTeam and TeCoEvent=TfcEvent
			INNER JOIN Events ON EvCode=TfcEvent AND EvTeamEvent=1 AND EvTournament=EnTournament
			INNER JOIN Teams ON TfcCoId=TeCoId AND TfcSubTeam=TeSubTeam AND TfcEvent=TeEvent AND TeTournament=EnTournament AND TeFinEvent=1
			INNER JOIN TeamFinals ON TfEvent=EvCode AND TfTournament=EnTournament and TfTeam=TeCoId and TfSubTeam=TeSubTeam";
		$ExtraWhere="";
		$ExtraGroup='Group By EnId, EvCode';
		if(empty($SORT)) $SORT='Printed, EvProgr, MatchNo, TfcOrder';
		if(!empty($_REQUEST['PrintNotPrinted']) and empty($BibNumber)) {
			$Where[] = ' AND (TeBacknoPrinted is NULL or TeBacknoPrinted=0) ';
		}
		if(isset($_REQUEST['Phase']) and strlen($_REQUEST['Phase'])!=0 and $_REQUEST['Phase']!=-1) {
			$Phase=valueFirstPhase(intval($_REQUEST['Phase']));
// 			if($Phase==24) {
// 				$Phase=32;
// 			} elseif($Phase==48) {
// 				$Phase=64;
// 			}
			$ExtraSql.=' inner join Grids ON TfMatchNo=GrMAtchNo AND GrPhase='.$Phase;
		}

		if(!empty($_REQUEST['Specifics'])) {
			$f=array();
			foreach($_REQUEST['Specifics'] as $ToId=>$specs) {
				foreach($specs as $k=>$v) {
					$f[]="(IcTournament=$ToId and IcNumber=$k and find_in_set(EvCode, '$v'))";
				}
			}
			$ExtraSql.=" inner join IdCards on IcTournament=EnTournament and IcType='$CardType' and (".implode(' or ', $f).") ";
		}

		break;
	case 'Y': // Diploma Individual
		$FIELDS.=", EvCode, EvEventName, QuScore as `QRScore`, IndRank as `Rank`, IndRankFinal as RankFinal, EvCode ExtraCode, (dextra.EdEmail is not null and dextra.EdEmail!='') as Printed, '' as TargetNo ";
		$ExtraSql="INNER JOIN Individuals ON IndTournament=EnTournament AND EnId=IndId
			INNER JOIN Events ON EvCode=IndEvent AND EvTeamEvent=0 AND EvTournament=EnTournament
			LEFT JOIN ExtraData dextra on dextra.EdId=EnId and dextra.EdType='D' and dextra.EdEvent=EvCode";
		$ExtraWhere="";
		$ExtraGroup='Group By EnId, EvCode';
		if(empty($SORT)) {
			$SORT='Printed, EvProgr, IndRankFinal, IndRank, EnFirstname, EnName';
		}
		if(!empty($_REQUEST['PrintNotPrinted']) and empty($BibNumber)) {
			$Where[] = " AND (dextra.EdEmail is null or dextra.EdEmail='') ";
		}
		if(isset($_REQUEST['Phase']) and strlen($_REQUEST['Phase'])!=0 and $_REQUEST['Phase']!=-1) {
			$Phase=valueFirstPhase(intval($_REQUEST['Phase']));
			$ExtraSql.=' inner join Grids ON FinMatchNo=GrMAtchNo AND GrPhase='.$Phase;
		}

		if(!empty($_REQUEST['TopRanked']) and ($TopRanked=intval($_REQUEST['TopRanked']))) {
			$Where[] = " AND IndRank between 1 and $TopRanked ";
		}
		if(!empty($_REQUEST['TopRankedFinal']) and ($TopRanked=intval($_REQUEST['TopRankedFinal']))) {
			$Where[] = " AND IndRankFinal between 1 and $TopRanked ";
		}

		if(!empty($_REQUEST['Specifics'])) {
			$f=array();
			foreach($_REQUEST['Specifics'] as $ToId=>$specs) {
				foreach($specs as $k=>$v) {
					$f[]="(IcTournament=$ToId and IcNumber=$k and find_in_set(EvCode, '$v'))";
				}
			}
			$ExtraSql.=" inner join IdCards on IcTournament=EnTournament and IcType='$CardType' and (".implode(' or ', $f).") ";
		}
		break;
	case 'Z': // Diploma Teams
		// needs to select all components of all teams...
		// during printout missing values will be printed as "-"
		$FIELDS.=", EvCode, EvEventName, if(Type&1, TeRank, '-') as `Rank`, if(Type&2 or TeRankFinal>=EvWinnerFinalRank+EvNumQualified, TeRankFinal,'-') as RankFinal, EvCode ExtraCode, (dextra.EdcEmail is not null and dextra.EdcEmail!='') as Printed, '' as TargetNo ";
		$ExtraSql="INNER JOIN (select TcCoId, TcSubTeam, TcEvent, TcId, sum(Type) as Type FROM
				(
				(select TcCoId, TcSubTeam, TcEvent, TcId, 1 as Type from TeamComponent where TcTournament in ($TourId) and TcFinEvent=1)
				union
				(select TfcCoId, TfcSubTeam, TfcEvent, TfcId, 2 as Type from TeamFinComponent where TfcTournament in ($TourId) )
				    ) joined
				    group BY
				    TcCoId, TcSubTeam, TcEvent, TcId) TeamComponents on TcId=EnId
			INNER JOIN Events ON EvCode=TcEvent AND EvTeamEvent=1 AND EvTournament=EnTournament
			INNER JOIN Teams ON TcCoId=TeCoId AND TcSubTeam=TeSubTeam AND TcEvent=TeEvent AND TeTournament=EnTournament AND TeFinEvent=1
			LEFT JOIN ExtraDataCountries dextra on dextra.EdcId=c.CoId and EdcSubTeam=TeSubTeam and dextra.EdcType='D' and dextra.EdcEvent=EvCode";
		$ExtraWhere="";
		$ExtraGroup='Group By EnId, EvCode';
		if(empty($SORT)) {
			$SORT='Printed, EvProgr, TeRankFinal, TeRank, EnFirstname, EnName';
		}
		if(!empty($_REQUEST['PrintNotPrinted']) and empty($BibNumber)) {
			$Where[] = " AND (dextra.EdcEmail is null or dextra.EdcEmail='') ";
		}

		if(!empty($_REQUEST['TopRanked']) and ($TopRanked=intval($_REQUEST['TopRanked']))) {
			$Where[] = " AND TeRank between 1 and $TopRanked ";
		}
		if(!empty($_REQUEST['TopRankedFinal']) and ($TopRanked=intval($_REQUEST['TopRankedFinal']))) {
			$Where[] = " AND TeRankFinal between 1 and $TopRanked ";
		}

		if(!empty($_REQUEST['Specifics'])) {
			$f=array();
			foreach($_REQUEST['Specifics'] as $ToId=>$specs) {
				foreach($specs as $k=>$v) {
					$f[]="(IcTournament=$ToId and IcNumber=$k and find_in_set(EvCode, '$v'))";
				}
			}
			$ExtraSql.=" inner join IdCards on IcTournament=EnTournament and IcType='$CardType' and (".implode(' or ', $f).") ";
		}
		break;
}

if(empty($SORT)) {
    $SORT='Printed, FirstName, Name';
}

$EntryWhere='';
if(!empty($_REQUEST['Entries'])) {
	$tmp=array();
	foreach($_REQUEST['Entries'] as $item) {
		if(intval($item)) $tmp[]=intval($item);
	}
	if($tmp) {
		sort($tmp);
		$Where[]=($EntryWhere='AND EnId in ('.implode(',', $tmp).')');
	}
}

if(!empty($_REQUEST['Country'])) {
	$tmp=array();
	foreach($_REQUEST['Country'] as $item) {
		if(intval($item)) $tmp[]=intval($item);
	}
	if($tmp) {
		sort($tmp);
		$Where[]='AND EnCountry in ('.implode(',', $tmp).')';
	}
}

if(!empty($_REQUEST['Division'])) {
	$tmp=array();
	foreach($_REQUEST['Division'] as $item) {
		if($item) $tmp[]=strsafe_DB($item);
	}
	if($tmp) {
		sort($tmp);
		$Where[]='AND EnDivision in ('.implode(',', $tmp).')';
	}
}

if(!empty($_REQUEST['Class'])) {
	$tmp=array();
	foreach($_REQUEST['Class'] as $item) {
		if($item) $tmp[]=strsafe_DB($item);
	}
	if($tmp) {
		sort($tmp);
		$Where[]='AND EnClass in ('.implode(',', $tmp).')';
	}
}

if(!empty($_REQUEST['Session'])) {
	$tmp=array();
	foreach($_REQUEST['Session'] as $item) {
		if(intval($item)) $tmp[]=intval($item);
	}
	if($tmp) {
		sort($tmp);
		$Where[]='AND QuSession in ('.implode(',', $tmp).')';
	}
}

if(!empty($_REQUEST['QSession'])) {
	$tmp=array();
	foreach($_REQUEST['QSession'] as $item) {
		if(intval($item)) $tmp[]=intval($item);
	}
	if($tmp) {
		sort($tmp);
		$Where[]=' AND QuSession in ('.implode(',', $tmp).')';
	}
}

if(!empty($_REQUEST['ESession'])) {
	$tmp=array();
	foreach($_REQUEST['ESession'] as $item) {
		if(intval($item)) $tmp[]=intval($item);
	}
	if($tmp) {
		sort($tmp);
		$Where[]=' AND ElSession in ('.implode(',', $tmp).')';
	}
}

if(!empty($_REQUEST['Event'])) {
	$tmp=array();
	foreach($_REQUEST['Event'] as $item) {
		if($item) $tmp[]=strsafe_DB($item);
	}
	if($tmp) {
		sort($tmp);
		$Where[]=' AND EvCode in ('.implode(',', $tmp).')';
	}
}

if(!empty($BibNumber)) {
	$Where[]=' AND (EnCode = '.StrSafe_DB(trim($BibNumber)).' or zextra.EdExtra = '.StrSafe_DB(trim($BibNumber)).')';
	$IncludePhoto    = false;
} else {
	$IncludePhoto    = (!empty($_REQUEST['IncludePhoto']));

	if(!empty($_REQUEST['PrintPhoto'])) {
		// recupera solo i badge che hanno una foto dentro
		$Where[] = ' AND PhPhoto is not NULL AND PhPhoto>\'\' ';
	}

	if(!empty($_REQUEST['PrintAccredited'])) {
		// recupera solo quelli già accreditati
		$Where[] = ' AND AEId is not NULL and AEOperation=1 ';
	}
}




$MyQuery = "SELECT $FIELDS, QuTargetNo as AbcdTarget
	FROM Entries AS e
	INNER JOIN Tournament on EnTournament=ToId
	INNER JOIN Qualifications AS q ON e.EnId=q.QuId
	INNER JOIN Divisions ON TRIM(EnDivision)=TRIM(DivId) AND DivTournament=EnTournament
	INNER JOIN Classes ON TRIM(EnClass)=TRIM(ClId) AND ClTournament=EnTournament
	INNER JOIN Countries AS c ON e.EnCountry=c.CoId AND e.EnTournament=c.CoTournament
    LEFT JOIN Countries AS c2 ON e.EnCountry2=c2.CoId AND e.EnTournament=c2.CoTournament
    LEFT JOIN Countries AS c3 ON e.EnCountry3=c3.CoId AND e.EnTournament=c3.CoTournament
	$ExtraSql
	LEFT JOIN AccEntries ON AEId=EnId and AETournament=EnTournament and AeOperation=1
	LEFT JOIN Session ON SesTournament=EnTournament and SesOrder=$QuSession and SesType='$QuSesLetter'
	LEFT JOIN AccColors AS ac ON ac.AcTournament=e.EnTournament AND CONCAT(TRIM(EnDivision),TRIM(EnClass)) LIKE ac.AcDivClass
	LEFT JOIN Photos AS ph ON e.EnId=ph.PhEnId
	LEFT JOIN ExtraData cextra ON e.EnId=cextra.EdId and cextra.EdType='C'
	LEFT JOIN ExtraData aextra ON e.EnId=aextra.EdId and aextra.EdType='A'
	LEFT JOIN ExtraData zextra ON e.EnId=zextra.EdId and zextra.EdType='Z'
    LEFT JOIN ExtraData pextra ON e.EnId=pextra.EdId and pextra.EdType='P'
    LEFT JOIN Rankings on EnTournament=RankTournament and RankEvent=IF(EvWaCategory!='',EvWaCategory,EvCode) and RankTeam=0 and EnCode=RankCode and ToIocCode='FITA' and EnIocCode in ('', 'FITA') and RankIocCode='FITA'
	WHERE EnTournament in ($TourId)
	$SpecialFilter
	".implode(' ', $Where)."
	$ExtraWhere
	$ExtraGroup
	ORDER BY  ".(empty($SORTSTRICT) ? "EnTournament, IcNumber, $SORT, Bib" : $SORTSTRICT);

if(!empty($_REQUEST['SortACBD'])) {
    $MyQuery="select realQuery.*, AtTargetNo
        from AvailableTarget
        left join ($MyQuery) realQuery on AtTargetNo=AbcdTarget and AtTournament=EnTournament
        where AtTournament in ($TourId) and (AtTarget, AtSession, AtTournament) in (
            select distinct QuTarget, QuSession, EnTournament 
            from Qualifications 
            inner join Entries on EnId=QuId and EnTournament in ($TourId)
            ".implode(' ', $Where)."
            )
        order by AtSession, AtTarget, AtLetter in ('B','D'), AtLetter";
}
