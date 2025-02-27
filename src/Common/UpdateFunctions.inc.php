<?php
require_once('Common/Lib/Obj_RankFactory.php');
require_once('Common/Fun_Various.inc.php');
require_once('Common/Lib/ArrTargets.inc.php');
require_once('Qualification/Fun_Qualification.local.inc.php');

function updateEnTimeStamp_20101126($TournamentID)
{
	$MySql="UPDATE Entries
		INNER JOIN Qualifications On EnId=QuId
		SET EnTimestamp=QuTimestamp
		WHERE EnTournament=" . StrSafe_DB($TournamentID);
	safe_w_SQL($MySql);
}

function recalculateIndividuals_20101211($TournamentID)
{
	// Popolo la tabella degli Individuals
	$events=array();
	MakeIndividuals($events,$TournamentID);
	// Ottengo il numero di Distanze
	$MySql = "SELECT ToNumDist FROM Tournament WHERE ToId=" . StrSafe_DB($TournamentID);
	$rs = safe_r_SQL($MySql);
	$MyRow = safe_fetch($rs);
	safe_free_result($rs);
	// Calcolo la tabella Individuals per ogni distanza + il finale
	for($i=0; $i<=$MyRow->ToNumDist; $i++)
	{
		$rank = Obj_RankFactory::create('Abs',array('tournament'=>$TournamentID,'dist'=>$i,'skipExisting'=>1));
		if($rank)
			$rank->calculate();
	}
	//Prendo le posizione dei Coin toss dalla tabella delle finali - SE senza eliminatorie
	$MySql = "UPDATE Individuals
		INNER JOIN Finals ON IndId=FinAthlete AND IndEvent=FinEvent AND IndTournament=FinTournament
		INNER JOIN Events ON EvCode=FinEvent AND EvTeamEvent=0 AND EvTournament=FinTournament
		INNER JOIN Grids ON GrMatchNo=FinMatchNo AND GrPhase=IF(EvFinalFirstPhase=24,32,EvFinalFirstPhase)
		SET IndRank=GrPosition
		WHERE FinTournament='{$TournamentID}' AND FinAthlete!=0 AND (EvElim1=0 AND EvElim2=0)";
	safe_w_SQL($MySql);
	// Gestisco le posizioni a seguito dello shootoff di entrata - SE le IndRank sono a 0
	$MySql = "SELECT IndId, IndEvent, QuScore, QuGold, QuXnine, IndRank
		FROM Individuals
		INNER JOIN Qualifications ON IndId=QuId
		INNER JOIN Events ON EvCode=IndEvent AND EvTeamEvent=0 AND EvTournament=IndTournament
		LEFT JOIN Finals ON IndTournament=FinTournament AND IndEvent=FinEvent AND IndId=FinAthlete
		LEFT JOIN Eliminations AS e1 ON e1.ElElimPhase=0 AND IndTournament=e1.ElTournament AND IndEvent=e1.ElEventCode AND IndId=e1.ElId
		LEFT JOIN Eliminations AS e2 ON e2.ElElimPhase=1 AND IndTournament=e2.ElTournament AND IndEvent=e2.ElEventCode AND IndId=e2.ElId
		WHERE IndTournament='{$TournamentID}' AND IndSO=1 AND IndRank=0 AND ((EvElim2=0 AND FinAthlete IS NULL) OR (EvElim2>0 AND EvElim1=0 AND e2.ElId IS NULL) OR (EvElim2>0 AND EvElim1>0 AND e1.ElId IS NULL))
		ORDER BY IndEvent, QuScore DESC, QuGold DESC, QuXnine DESC, IndId
		";
	$rs = safe_r_SQL($MySql);
	$curGroup = "-----";
	$myPos = -1;
	$myRank = -1;
	$oldScore = -1;
	$oldGold = -1;
	$oldXnine = -1;
	while($MyRow = safe_fetch($rs))
	{
		if($curGroup != $MyRow->IndEvent)
		{
			$curGroup = $MyRow->IndEvent;
			$myPos = ($MyRow->IndRank);
		}
		$myPos++;
		if($MyRow->QuScore != $oldScore || $MyRow->QuGold != $oldGold || $MyRow->QuXnine != $oldXnine)
			$myRank=$myPos;

		$MySql = "UPDATE Individuals
			SET IndRank = {$myRank}
			WHERE IndId='{$MyRow->IndId}' AND IndEvent='{$MyRow->IndEvent}' AND IndTournament='{$TournamentID}'";
		safe_w_SQL($MySql);
		$oldScore  = $MyRow->QuScore;
		$oldGold = $MyRow->QuGold;
		$oldXnine = $MyRow->QuXnine;
	}
	//Sistemo le Rank di quelli che NON hanno passato i gironi ELiminatori (se c'erano i gironi) e i flag di SO/CT
	$MySql = "SELECT EvCode, EvFinalFirstPhase, EvElim1, EvElim2 FROM Events WHERE (EvElim1!=0 OR EvElim2!=0) AND EvTournament=" . StrSafe_DB($TournamentID) . " AND EvTeamEvent=0";
	$rs = safe_r_SQL($MySql);
	$eventsC=array();
	while($MyRow = safe_fetch($rs))
	{
		if($MyRow->EvElim1>0)
			$eventsC[] = $MyRow->EvCode . "@1";
		if($MyRow->EvElim2>0)
			$eventsC[] = $MyRow->EvCode . "@2";
	}
	Obj_RankFactory::create('ElimInd',array('tournament'=>$TournamentID,'eventsC'=>$eventsC,'skipExisting'=>1))->calculate();

/*
	$MySql = "SELECT ElId, ElElimPhase, ElEventCode, ElQualRank, ElScore, ElGold, ElXnine, ElRank
		FROM Eliminations
		INNER JOIN Events ON EvCode=ElEventCode AND EvTeamEvent=0 AND EvTournament=ElTournament
		WHERE ElTournament='{$TournamentID}' AND  ((EvElim1>0 AND EvE1ShootOff!=0 AND ElElimPhase=0) OR (EvElim2>0 AND EvE2ShootOff!=0 AND ElElimPhase=1))
		ORDER BY ElEventCode, ElElimPhase, ElScore DESC, ElRank ASC, ElGold DESC, ElXnine DESC, ElId
		";
	$rs = safe_r_SQL($MySql);
	$curGroup = "-----";
	$myPos = -1;
	$myRank = -1;
	$oldScore = -1;
	$oldGold = -1;
	$oldXnine = -1;
	while($MyRow = safe_fetch($rs))
	{
		if($curGroup != $MyRow->ElElimPhase . "|". $MyRow->ElEventCode)
		{
			$curGroup = $MyRow->ElElimPhase . "|". $MyRow->ElEventCode;
			$myPos = 0;
		}
		$myPos++;
		if($MyRow->ElScore != $oldScore || $MyRow->ElGold != $oldGold || $MyRow->ElXnine != $oldXnine)
			$myRank=$myPos;

		if($MyRow->ElRank == 0)
		{
			$MySql = "UPDATE Eliminations
				SET ElRank = {$myRank}
				WHERE ElElimPhase='{$MyRow->ElElimPhase}' AND ElEventCode='{$MyRow->ElEventCode}' AND ElTournament='{$TournamentID}' AND ElQualRank='{$MyRow->ElQualRank}'";
			safe_w_SQL($MySql);
		}
		$oldScore  = $MyRow->ElScore;
		$oldGold = $MyRow->ElGold;
		$oldXnine = $MyRow->ElXnine;
	}
*/
	// Calcolo le rank Finali venendo dalle qualifiche
	$MySql = "SELECT EvCode, EvFinalFirstPhase, EvElim1, EvElim2 FROM Events WHERE EvTournament=" . StrSafe_DB($TournamentID) . " AND EvTeamEvent=0";
	$rs = safe_r_SQL($MySql);
	$eventsC=array();
	while($MyRow = safe_fetch($rs))
	{
		$eventsC[] = $MyRow->EvCode . "@-3";
		if($MyRow->EvElim1>0)
			$eventsC[] = $MyRow->EvCode . "@-1";
		if($MyRow->EvElim2>0)
			$eventsC[] = $MyRow->EvCode . "@-2";
		$eventsC[] = $MyRow->EvCode . "@" . $MyRow->EvFinalFirstPhase;
	}
	Obj_RankFactory::create('FinalInd',array('tournament'=>$TournamentID,'eventsC'=>$eventsC))->calculate();
	safe_free_result($rs);
}

function calcMaxTeamPerson_20110216($TournamentID)
{
	$events=array();

	$q="
		SELECT EvCode
		FROM
			Events
		WHERE
			EvTournament={$TournamentID} AND EvTeamEvent=1
	";
	$r=safe_r_sql($q);

	if (safe_num_rows($r)>0)
	{
		while ($row=safe_fetch($r))
		{
			$events[]=$row->EvCode;
		}
	}

	calcMaxTeamPerson($events,true,$TournamentID);
}

function recalculateTeamRanking_20110216($TournamentID)
{
// per tutte le squadre del torneo ricalcolo le 3 rank
	$rank=Obj_RankFactory::create('DivClassTeam',array('tournament'=>$TournamentID));
	if ($rank)
		$rank->calculate();

	$rank=Obj_RankFactory::create('AbsTeam',array('tournament'=>$TournamentID));
	if ($rank)
		$rank->calculate();

	$MySql = "SELECT EvCode, EvFinalFirstPhase FROM Events WHERE EvTournament=" . StrSafe_DB($TournamentID) . " AND EvTeamEvent=1";
	$rs = safe_r_SQL($MySql);
	$eventsC=array();
	while($MyRow = safe_fetch($rs))
	{
		$eventsC[] = $MyRow->EvCode . "@-3";
		$eventsC[] = $MyRow->EvCode . "@" . $MyRow->EvFinalFirstPhase;
	}
	$rank=Obj_RankFactory::create('FinalTeam',array('eventsC'=>$eventsC, 'tournament'=>$TournamentID));
	if ($rank)
		$rank->calculate();
}

function getMapsGoldsXNineChars_20110309()
{
	// mappa x i gold
	$goldMap=array(
		'10'=>'L',
		'6+5'=>'FG',
		'11'=>'M'
	);

	$xnineMap=array(
		'X'=>'K',
		'9'=>'J',
		'6'=>'G',
		'10'=>'L'
	);

	return array('G'=>$goldMap,'X'=>$xnineMap);
}


function initTourGoldsXNineChars_20110309($TournamentID)
{
	$maps=getMapsGoldsXNineChars_20110309();

	$sql="SELECT ToGolds,ToXNine FROM Tournament WHERE ToId={$TournamentID} ";
	$r=safe_r_sql($sql);

	if ($r && safe_num_rows($r)==1)
	{
		$row=safe_fetch($r);

		$gold=$maps['G'][$row->ToGolds];
		$xnine=$maps['X'][$row->ToXNine];

		$sql="UPDATE Tournament SET ToGoldsChars='{$gold}',ToXNineChars='{$xnine}' WHERE ToId={$TournamentID} ";
		$r=safe_w_sql($sql);
	}
}

function RecalcFinRank_20110415($TournamentID)
{
// per tutti gli eventi ricalcolo le rank finali
	$q="
		SELECT
			EvCode,IF(EvTeamEvent=0,'I','T') AS `Team`,EvFinalFirstPhase,EvElim1,EvElim2
		FROM
			Events
		WHERE
			EvTournament={$TournamentID}
	";
	$r=safe_r_sql($q);

	if (safe_num_rows($r)>0)
	{
		$eventsI=array();
		$eventsT=array();

		while ($row=safe_fetch($r))
		{
		// calcolo di sicuro chi si è fermato agli assoluti
			${'events'.$row->Team}[]=$row->EvCode.'@-3';

		// se ho un girone elim
			if ($row->EvElim2!=0 && $row->EvElim1==0)
			{
				${'events'.$row->Team}[]=$row->EvCode.'@-2';
			}
		// e se ne ho due
			elseif ($row->EvElim2!=0 && $row->EvElim1!=0)
			{
				${'events'.$row->Team}[]=$row->EvCode.'@-1';
				${'events'.$row->Team}[]=$row->EvCode.'@-2';
			}

		// dalla prima fase finale
			${'events'.$row->Team}[]=$row->EvCode.'@'.$row->EvFinalFirstPhase;
		}

		Obj_RankFactory::create('FinalInd',array('eventsC'=>$eventsI,'tournament'=>$TournamentID))->calculate();
		Obj_RankFactory::create('FinalTeam',array('eventsC'=>$eventsT,'tournament'=>$TournamentID))->calculate();
	}
}

function Update3DIta_20120111($TournamentID)
{
	$q="
			UPDATE Tournament
			SET ToTypeSubRule='Set1Dist1Arrow'
			WHERE
				ToId={$TournamentID} AND ToLocRule='IT' AND ToType=11 AND ToTypeSubRule=''
		";
	$r=safe_w_sql($q);
}

function UpdateWinLose_20140322($TourId=0) {
	// Updating Winner of finals up to semifinals
	safe_w_sql("update Finals f1
			inner join Finals f2 on f1.FinTournament=f2.FinTournament and f1.FinEvent=f2.FinEvent and f1.FinAthlete=f2.FinAthlete and (f2.FinMatchNo=floor(f1.FinMatchNo/2) or (f1.FinMatchNo in (4,5,6,7) and f2.FinMatchNo in (0,1))) and f2.FinMatchNo!=f1.FinMatchNo
			set f1.FinWinLose=1
			where
			f2.FinMatchNo not in (2,3) and
			f1.FinAthlete!=0"
			.($TourId ? " and f1.FinTournament=$TourId" : ''));

	safe_w_sql("update TeamFinals f1
			inner join TeamFinals f2 on f1.TfTournament=f2.TfTournament and f1.TfEvent=f2.TfEvent and f1.TfTeam=f2.TfTeam and f1.TfSubTeam=f2.TfSubTeam and (f2.TfMatchNo=floor(f1.TfMatchNo/2) or (f1.TfMatchNo in (4,5,6,7) and f2.TfMatchNo in (0,1))) and f2.TfMatchNo!=f1.TfMatchNo
			set f1.TfWinLose=1
			where
			f2.TfMatchNo not in (2,3) and
			f1.TfTeam!=0"
			.($TourId ? " and f1.TfTournament=$TourId" : ''));

	// Update the medal matches
	safe_w_sql("Update Finals
			inner join Individuals on FinTournament=IndTournament and FinEvent=IndEvent and FinAthlete=IndId and FinMatchNo<4
			set FinWinLose=1
			where IndRankFinal in (1,3)"
			.($TourId ? " and FinTournament=$TourId" : ''));

	safe_w_sql("Update TeamFinals
			inner join Teams on TfTournament=TeTournament and TeEvent=TeEvent and TfTeam=TeCoId and TfSubTeam=TeSubTeam and TfMatchNo<4
			set TfWinLose=1
			where TeRankFinal in (1,3)"
			.($TourId ? " and TfTournament=$TourId" : ''));
}

function UpdateItaRules_20140401($TourId=0) {
	safe_w_sql("UPDATE Tournament SET ToGolds='6',ToXNine='5',ToGoldsChars='G',ToXNineChars= 'F' WHERE ToId=$TourId AND ToLocRule='IT' AND ToWhenFrom>='2014-04-01' AND ToType IN (9,10,12)");
	safe_w_sql("UPDATE Tournament SET ToGolds='11',ToXNine='10',ToGoldsChars='M',ToXNineChars= 'L' WHERE ToId=$TourId AND ToLocRule='IT' AND ToWhenFrom>='2014-04-01' AND ToType IN (11,13)");
	safe_w_sql("UPDATE Events INNER JOIN Tournament ON EvTournament=ToId SET EvMatchMode=1 WHERE ToId=$TourId AND ToLocRule='IT' AND ToWhenFrom>='2014-04-01' AND ToType IN (1,2,3,4,6,7,8,18) AND LEFT(EvCode,2)='OL'");
	safe_w_sql("UPDATE Events INNER JOIN Tournament ON EvTournament=ToId SET EvMatchMode=0 WHERE ToId=$TourId AND ToLocRule='IT' AND ToWhenFrom>='2014-04-01' AND ToType IN (1,2,3,4,6,7,8,18) AND LEFT(EvCode,2)='CO'");
	safe_w_sql("UPDATE Events INNER JOIN Tournament ON EvTournament=ToId SET EvMatchMode=1 WHERE ToId=$TourId AND ToLocRule='IT' AND ToWhenFrom>='2014-04-01' AND ToType IN (6,7,8) AND LEFT(EvCode,2)='AN'");
}

function UpdateArrowPosition_20141115($TourId=0) {
	$Sql = "SELECT FinEvent, FinMatchNo, FinTournament, FinArrowPosition, FinTiePosition, EvFinalTargetType, EvTargetSize
		FROM Finals
		INNER JOIN Events ON EvCode=FinEvent AND EvTeamEvent=0 AND EvTournament=FinTournament
		WHERE FinTournament={$TourId} AND (LENGTH(`FinArrowPosition`)>0 OR LENGTH(`FinTiePosition`)>0)
		ORDER BY FinEvent, FinMatchno";
	$r = safe_r_SQL($Sql);
	while($row = safe_fetch($r)) {
		$oldArr = explode("|",trim($row->FinArrowPosition));
		$newArr = array();
		$oldTie = explode("|",trim($row->FinTiePosition));
		$newTie = array();
		$size=($row->EvTargetSize ? $row->EvTargetSize : 122) * 50;
		switch($row->EvFinalTargetType) {
			case 2:
			case 4:
			case 10:
				$size *= 0.5;
				break;
			case 9:
				$size *= 0.6;
				break;
			case 7:
				if(substr($row->FinEvent,0,1)=="C" && $size==6100)
					$size = 80*50;
				break;
		}
		foreach($oldArr as $k=>$v) {
			if(!empty($v) && strpos($v,",")!==false) {
				$tmp = explode(",",$v);
				$newArr[$k] = array(round($size*$tmp[0]/1000,0,PHP_ROUND_HALF_DOWN), round(-1*$size*$tmp[1]/1000,0,PHP_ROUND_HALF_DOWN));
			}
		}
		foreach($oldTie as $k=>$v) {
			if(!empty($v) && strpos($v,",")!==false) {
				$tmp = explode(",",$v);
				$newTie[$k] = array(round($size*$tmp[0]/1000,0,PHP_ROUND_HALF_DOWN), round(-1*$size*$tmp[1]/1000,0,PHP_ROUND_HALF_DOWN));
			}
		}

		$Sql = "UPDATE Finals SET
			FinArrowPosition = '" . (count($newArr) ? serialize($newArr) : "") . "',
			FinTiePosition = '" . (count($newTie) ? serialize($newTie) :  "") . "'
			WHERE FinEvent='{$row->FinEvent}' AND FinMatchNo={$row->FinMatchNo} AND FinTournament={$row->FinTournament}";
		safe_w_SQL($Sql);
	}

	$Sql = "SELECT TfEvent, TfMatchNo, TfTournament, TfArrowPosition, TfTiePosition, EvFinalTargetType, EvTargetSize
		FROM TeamFinals
		INNER JOIN Events ON EvCode=TfEvent AND EvTeamEvent=1 AND EvTournament=TfTournament
		WHERE TfTournament={$TourId} AND (LENGTH(`TfArrowPosition`)>0 OR LENGTH(`TfTiePosition`)>0)
		ORDER BY TfEvent, TfMatchno";
	$r = safe_r_SQL($Sql);
	while($row = safe_fetch($r)) {
		$oldArr = explode("|",trim($row->TfArrowPosition));
		$newArr = array();
		$oldTie = explode("|",trim($row->TfTiePosition));
		$newTie = array();
		$size=($row->EvTargetSize ? $row->EvTargetSize : 122) * 50;
		switch($row->EvFinalTargetType) {
			case 2:
			case 4:
			case 10:
				$size *= 0.5;
				break;
			case 9:
				$size *= 0.6;
				break;
			case 7:
				if(substr($row->FinEvent,0,1)=="C" && $size==6100)
					$size = 80*50;
					break;
		}
		foreach($oldArr as $k=>$v) {
			if(!empty($v) && strpos($v,",")!==false) {
				$tmp = explode(",",$v);
				$newArr[$k] = array(round($size*$tmp[0]/1000,0,PHP_ROUND_HALF_DOWN), round(-1*$size*$tmp[1]/1000,0,PHP_ROUND_HALF_DOWN));
			}
		}
		foreach($oldTie as $k=>$v) {
			if(!empty($v) && strpos($v,",")!==false) {
				$tmp = explode(",",$v);
				$newTie[$k] = array(round($size*$tmp[0]/1000,0,PHP_ROUND_HALF_DOWN), round(-1*$size*$tmp[1]/1000,0,PHP_ROUND_HALF_DOWN));
			}
		}

		$Sql = "UPDATE TeamFinals SET
			TfArrowPosition = '" . (count($newArr) ? serialize($newArr) : "") . "',
			TfTiePosition = '" . (count($newTie) ? serialize($newTie) :  "") . "'
			WHERE TfEvent='{$row->TfEvent}' AND TfMatchNo={$row->TfMatchNo} AND TfTournament={$row->TfTournament}";
		safe_w_SQL($Sql);
	}
}

function UpdateToOptions_20150304($ToId=0) {
	$q=safe_r_sql("select ToOptions from Tournament where ToId=$ToId and ToOptions>''");
	if($r=safe_fetch($q)) {
		$v=unserialize($r->ToOptions);
		if(isset($v['ISK-Lite-Mode'])) {
			require_once('Common/Lib/Fun_Modules.php');
			setModuleParameter('ISK', 'ServerUrl', $v['ISK-Lite-ServerUrl']);
			setModuleParameter('ISK', 'Mode', $v['ISK-Lite-Mode']);
			unset($v['ISK-Lite-ServerUrl']);
			unset($v['ISK-Lite-Mode']);
			unset($v['ISK-ServerUrl']);
			safe_w_sql("update Tournament set ToOptions=".StrSafe_DB(serialize($v))." where ToId=$ToId");
		}
	}
}

function UpdateSetPointsByEnd_20150416($ToId=0) {
	// update Individuals Set Points by End
	$sql = "SELECT * from (
			select
				EvCode Event, @ArBit:=(EvMatchArrowsNo & pow(2, if(FinMatchNo=0, 0, floor(LOG(2, FinMatchNo))))),
				if(@ArBit=0, EvFinArrows, EvElimArrows) Arrows, if(@ArBit=0, EvFinEnds, EvElimEnds) Ends,
				FinTournament Tournament,
				FinMatchNo MatchNo,
				FinSetScore as SetScore,
				FinSetPoints SetPoints,
				FinArrowstring arrowstring
			FROM Finals
			INNER JOIN Events ON FinEvent=EvCode AND FinTournament=EvTournament AND EvTeamEvent=0 AND EvFinalFirstPhase!=0 and EvMatchMode=1
			INNER JOIN Grids ON FinMatchNo=GrMatchNo
			WHERE FinMatchNo%2=0 and trim(FinArrowstring)!='' ".($ToId ? "and FinTournament=$ToId" : "")."
			) f1 inner join (
			select
				EvCode OppEvent,
				FinTournament OppTournament,
				FinMatchNo OppMatchNo,
				FinSetScore as OppSetScore,
				FinSetPoints OppSetPoints,
				FinArrowstring oppArrowstring
			FROM Finals
			INNER JOIN Events ON FinEvent=EvCode AND FinTournament=EvTournament AND EvTeamEvent=0 AND EvFinalFirstPhase!=0 and EvMatchMode=1
			INNER JOIN Grids ON FinMatchNo=GrMatchNo
			WHERE FinMatchNo%2=1 AND trim(FinArrowstring)!='' ".($ToId ? "and FinTournament=$ToId" : "")."
			) f2 on Tournament=OppTournament and Event=OppEvent and MatchNo=OppMatchNo-1
		ORDER BY event, MatchNo ASC ";
	$q=safe_r_sql($sql);
	while($r=safe_fetch($q)) {
		$SpBeSx=array();
		$SpBeRx=array();
		$SpSx=explode('|', $r->SetPoints);
		$SpRx=explode('|', $r->OppSetPoints);
		for($i=0; $i<count($SpSx); $i++) {
			$End   =substr($r->arrowstring,    $i*$r->Arrows, $r->Arrows);
			$OppEnd=substr($r->oppArrowstring, $i*$r->Arrows, $r->Arrows);

			if(!strstr($End, ' ') and !strstr($OppEnd, ' ') and strlen($End)==$r->Arrows and strlen($OppEnd)==$r->Arrows) {
				$SpBeSx[$i]=($SpSx[$i]>$SpRx[$i] ? 2 : ($SpSx[$i]==$SpRx[$i] ? 1 : 0));
				$SpBeRx[$i]=($SpSx[$i]<$SpRx[$i] ? 2 : ($SpSx[$i]==$SpRx[$i] ? 1 : 0));
			}
		}
		safe_w_sql("update Finals set FinSetPointsByEnd='".implode('|', $SpBeSx)."' where FinEvent='$r->Event' and FinTournament='$r->Tournament' and FinMatchNo='$r->MatchNo'");
		safe_w_sql("update Finals set FinSetPointsByEnd='".implode('|', $SpBeRx)."' where FinEvent='$r->Event' and FinTournament='$r->Tournament' and FinMatchNo='$r->OppMatchNo'");
	}

	// update Teams Set Points by End
	$sql = "SELECT * from (
			select
				EvCode Event, @ArBit:=(EvMatchArrowsNo & pow(2, if(TfMatchNo=0, 0, floor(LOG(2, TfMatchNo))))),
				if(@ArBit=0, EvFinArrows, EvElimArrows) Arrows, if(@ArBit=0, EvFinEnds, EvElimEnds) Ends,
				TfTournament Tournament,
				TfTeam Team,
				TfMatchNo MatchNo,
				TfSetScore as SetScore,
				TfSetPoints SetPoints,
				TfArrowstring arrowstring
			FROM TeamFinals
			INNER JOIN Events ON TfEvent=EvCode AND TfTournament=EvTournament AND EvTeamEvent=1 AND EvFinalFirstPhase!=0 and EvMatchMode=1
			INNER JOIN Grids ON TfMatchNo=GrMatchNo
			WHERE TfMatchNo%2=0 and trim(TfArrowstring)!='' ".($ToId ? "and TfTournament=$ToId" : "")."
			) f1 inner join (
			select
				EvCode OppEvent,
				TfTournament OppTournament,
				TfTeam OppTeam,
				TfMatchNo OppMatchNo,
				TfSetScore as OppSetScore,
				TfSetPoints OppSetPoints,
				TfArrowstring oppArrowstring
			FROM TeamFinals
			INNER JOIN Events ON TfEvent=EvCode AND TfTournament=EvTournament AND EvTeamEvent=1 AND EvFinalFirstPhase!=0 and EvMatchMode=1
			INNER JOIN Grids ON TfMatchNo=GrMatchNo
			WHERE TfMatchNo%2=1 AND trim(TfArrowstring)!='' ".($ToId ? "and TfTournament=$ToId" : "")."
			) f2 on Tournament=OppTournament and Event=OppEvent and MatchNo=OppMatchNo-1
		ORDER BY event, MatchNo ASC ";
	$q=safe_r_sql($sql);
	while($r=safe_fetch($q)) {
		$SpBeSx=array();
		$SpBeRx=array();
		$SpSx=explode('|', $r->SetPoints);
		$SpRx=explode('|', $r->OppSetPoints);
		for($i=0; $i<count($SpSx); $i++) {
			$End   =substr($r->arrowstring,    $i*$r->Arrows, $r->Arrows);
			$OppEnd=substr($r->oppArrowstring, $i*$r->Arrows, $r->Arrows);

			if(!strstr($End, ' ') and !strstr($OppEnd, ' ') and strlen($End)==$r->Arrows and strlen($OppEnd)==$r->Arrows) {
				$SpBeSx[$i]=($SpSx[$i]>$SpRx[$i] ? 2 : ($SpSx[$i]==$SpRx[$i] ? 1 : 0));
				$SpBeRx[$i]=($SpSx[$i]<$SpRx[$i] ? 2 : ($SpSx[$i]==$SpRx[$i] ? 1 : 0));
			}
		}
		safe_w_sql("update TeamFinals set TfSetPointsByEnd='".implode('|', $SpBeSx)."' where TfEvent='$r->Event' and TfTournament='$r->Tournament' and TfMatchNo='$r->MatchNo'");
		safe_w_sql("update TeamFinals set TfSetPointsByEnd='".implode('|', $SpBeRx)."' where TfEvent='$r->Event' and TfTournament='$r->Tournament' and TfMatchNo='$r->OppMatchNo'");
	}
}

function UpdateSessionsFromAgileModule_20160322($ToId=0) {
	require_once('Tournament/Fun_ManSessions.inc.php');
	$Sessions = getModuleParameter('Agile', 'Sessions', array(),$ToId);
	foreach($Sessions as $kSes=>$vSes) {
		insertSession($ToId,($kSes+1),'F',$vSes[5],0,0,0,0,($vSes[1].' '.$vSes[2]),($vSes[1].' '.$vSes[3]));
	}
}

function updateEliminationEvents_20170530($ToId=0) {
	$q=safe_r_sql("select * from Tournament where ToElimination!=0 and ToId=$ToId");
	if($r=safe_fetch($q)) {
		safe_w_sql("update Events set EvElimType=0 where EvTournament=$r->ToId and EvTeamEvent=0 and EvElim1=0 and EvElim2=0 and EvElimType!=3");
		safe_w_sql("update Events set EvElimType=2, EvE1Ends=EvElimEnds, EvE1Arrows=EvElimArrows, EvE1SO=EvElimSO where EvTournament=$r->ToId and EvTeamEvent=0 and EvElim1>0 and EvElimType!=3");
		safe_w_sql("update Events set EvElimType=if(EvElim1=0, 1, 2), EvE2Ends=8, EvE2Arrows=3, EvE2SO=1 where EvTournament=$r->ToId and EvTeamEvent=0 and EvElim2>0 and EvElimType!=3");
	}

}

function updateEliminationEvents_20180114($ToId=0) {
	$done=array();
	$q=safe_r_sql("select * from Events where Events.EvFinalFirstPhase>0 and EvTournament=$ToId order by EvFinalFirstPhase desc");
	while($r=safe_fetch($q)) {
		switch($r->EvFinalFirstPhase) {
			case '64': $Selected=128; $Pass=64; break;
			case '48': $Selected=104; $Pass=56; break;
			case '32': $Selected= 64; $Pass=32; break;
			case '24': $Selected= 56; $Pass=32; break;
			case '16': $Selected= 32; $Pass=16; break;
			case '14': $Selected= 28; $Pass=16; break;
			case '12': $Selected= 24; $Pass=16; break;
			case  '8': $Selected= 16; $Pass= 8; break;
			case  '7': $Selected= 14; $Pass= 8; break;
			case  '4': $Selected=  8; $Pass= 4; break;
			case  '2': $Selected=  4; $Pass= 0; break;
		}

		// TODO: calculate the first selected based on the outcome of previous phase
		//$First=1;
		//$done[$r->EvTeamEvent][$r->EvTeamEvent][$r->EvCode]=$Pass;
		//if(!empty($done[$r->EvTeamEvent][$r->EvTeamEvent][$r->EvCodeParent])) {
		//	$done[$r->EvTeamEvent][$r->EvTeamEvent][$r->EvCode]=$done[$r->EvTeamEvent][$r->EvTeamEvent][$r->EvCodeParent]+$Pass;
		//	$First=$done[$r->EvTeamEvent][$r->EvTeamEvent][$r->EvCodeParent]+1;
		//}
		safe_w_sql("update Events set EvNumQualified=$Selected where EvTournament=$ToId and EvTeamEvent=$r->EvTeamEvent and EvCode='$r->EvCode'");
	}
}

function updateArrowPositions_20180503($ToId=0) {
	$q=safe_r_SQL("select * from Finals where FinArrowPosition!=''".($ToId ? " and FinTournament=$ToId" : ''));
	while($r=safe_fetch($q)) {
		if($Arrows=@unserialize($r->FinArrowPosition)) {
			$New=array();
			foreach($Arrows as $k => $Arrow) {
				if(count($Arrow)==2) {
					// old system with only X and Y in 1/10 mm
					$New[$k] = array(
						"X" => round($Arrow[0]/10,1),
						"Y" => round($Arrow[1]/10,1),
						"R" => 2.5,
						"D" => round(sqrt($Arrow[0]*$Arrow[0] + $Arrow[1]*$Arrow[1])/10,1),
					);
				} elseif(isset($Arrow['X'])) {
					$New[$k] = array(
						"X" => round($Arrow['X'],1),
						"Y" => round($Arrow['Y'],1),
						"R" => round($Arrow['R'],1),
						"D" => round($Arrow['D'],1),
					);
				}
			}
			safe_w_sql("update Finals set FinArrowPosition=".StrSafe_DB(json_encode($New))." where FinEvent='$r->FinEvent' and FinMatchNo=$r->FinMatchNo and FinTournament=$r->FinTournament");
		}
	}

	$q=safe_r_SQL("select * from Finals where FinTiePosition!=''".($ToId ? " and FinTournament=$ToId" : ''));
	while($r=safe_fetch($q)) {
		if($Arrows=@unserialize($r->FinTiePosition)) {
			$New=array();
			foreach($Arrows as $k => $Arrow) {
				if(count($Arrow)==2) {
					// old system with only X and Y in 1/10 mm
					$New[$k] = array(
						"X" => round($Arrow[0]/10,1),
						"Y" => round($Arrow[1]/10,1),
						"R" => 2.5,
						"D" => round(sqrt($Arrow[0]*$Arrow[0] + $Arrow[1]*$Arrow[1])/10,1),
					);
				} elseif(isset($Arrow['X'])) {
					$New[$k] = array(
						"X" => round($Arrow['X'],1),
						"Y" => round($Arrow['Y'],1),
						"R" => round($Arrow['R'],1),
						"D" => round($Arrow['D'],1),
					);
				}
			}
			safe_w_sql("update Finals set FinTiePosition=".StrSafe_DB(json_encode($New))." where FinEvent='$r->FinEvent' and FinMatchNo=$r->FinMatchNo and FinTournament=$r->FinTournament");
		}
	}

	$q=safe_r_SQL("select * from TeamFinals where TfArrowPosition!=''".($ToId ? " and TfTournament=$ToId" : ''));
	while($r=safe_fetch($q)) {
		if($Arrows=@unserialize($r->TfArrowPosition)) {
			$New=array();
			foreach($Arrows as $k => $Arrow) {
				if(count($Arrow)==2) {
					// old system with only X and Y in 1/10 mm
					$New[$k] = array(
						"X" => round($Arrow[0]/10,1),
						"Y" => round($Arrow[1]/10,1),
						"R" => 2.5,
						"D" => round(sqrt($Arrow[0]*$Arrow[0] + $Arrow[1]*$Arrow[1])/10,1),
					);
				} elseif(isset($Arrow['X'])) {
					$New[$k] = array(
						"X" => round($Arrow['X'],1),
						"Y" => round($Arrow['Y'],1),
						"R" => round($Arrow['R'],1),
						"D" => round($Arrow['D'],1),
					);
				}
			}
			safe_w_sql("update TeamFinals set TfArrowPosition=".StrSafe_DB(json_encode($New))." where TfEvent='$r->TfEvent' and TfMatchNo=$r->TfMatchNo and TfTournament=$r->TfTournament");
		}
	}

	$q=safe_r_SQL("select * from TeamFinals where TfTiePosition!=''".($ToId ? " and TfTournament=$ToId" : ''));
	while($r=safe_fetch($q)) {
		if($Arrows=@unserialize($r->TfTiePosition)) {
			$New=array();
			foreach($Arrows as $k => $Arrow) {
				if(count($Arrow)==2) {
					// old system with only X and Y in 1/10 mm
					$New[$k] = array(
						"X" => round($Arrow[0]/10,1),
						"Y" => round($Arrow[1]/10,1),
						"R" => 2.5,
						"D" => round(sqrt($Arrow[0]*$Arrow[0] + $Arrow[1]*$Arrow[1])/10,1),
					);
				} elseif(isset($Arrow['X'])) {
					$New[$k] = array(
						"X" => round($Arrow['X'],1),
						"Y" => round($Arrow['Y'],1),
						"R" => round($Arrow['R'],1),
						"D" => round($Arrow['D'],1),
					);
				}
			}
			safe_w_sql("update TeamFinals set TfTiePosition=".StrSafe_DB(json_encode($New))." where TfEvent='$r->TfEvent' and TfMatchNo=$r->TfMatchNo and TfTournament=$r->TfTournament");
		}
	}
}

function updateArrowTimestamp_20180624($ToId=0)  {
	$q=safe_r_sql("select * from FinOdfTiming where FinOdfArrows!=''".($ToId ? " and FinOdfTournament=$ToId" : ''));
	while($r=safe_fetch($q)) {
		$ar=@unserialize($r->FinOdfArrows);
		if($ar) {
			$ab=array();
			foreach($ar as $k => $v) {
				$ab["$k"]=$v;
			}
			safe_w_sql("update FinOdfTiming set FinOdfArrows=".StrSafe_DB(json_encode($ab))." where FinOdfTournament=$r->FinOdfTournament and FinOdfTeamEvent=$r->FinOdfTeamEvent and FinOdfEvent='$r->FinOdfEvent' and FinOdfMatchno=FinOdfMatchno");
		}
	}
}

function updateTbClosest_20200404($ToId=0)  {
    $q=safe_r_SQL("SELECT IndId, IndEvent, IndTournament, IndTieBreak FROM Individuals WHERE ".($ToId ? "IndTournament={$ToId} AND" : "")." BINARY IndTieBreak!=UPPER(IndTieBreak)");
    while($r=safe_fetch($q)) {
        safe_w_SQL("UPDATE Individuals SET IndTieBreak=UPPER(IndTieBreak), IndTbClosest=1 WHERE IndId={$r->IndId} AND IndEvent='{$r->IndEvent}' AND IndTournament={$r->IndTournament}");
    }

    $q=safe_r_SQL("SELECT TeCoId, TeSubTeam, TeEvent, TeTournament, TeFinEvent, TeTieBreak FROM Teams WHERE ".($ToId ? "TeTournament={$ToId} AND" : "")." BINARY TeTieBreak!=UPPER(TeTieBreak)");
    while($r=safe_fetch($q)) {
        safe_w_SQL("UPDATE Teams SET TeTieBreak=UPPER(TeTieBreak), TeTbClosest=1 WHERE TeCoId={$r->TeCoId} AND TeSubTeam={$r->TeSubTeam} AND TeEvent='{$r->TeEvent}' AND TeTournament={$r->TeTournament} AND TeFinEvent={$r->TeFinEvent}");
    }

    $q=safe_r_SQL("SELECT FinEvent, FinMatchNo, FinTournament, FinTieBreak FROM Finals WHERE ".($ToId ? "FinTournament={$ToId} AND" : "")." BINARY FinTieBreak!=UPPER(FinTieBreak)");
    while($r=safe_fetch($q)) {
        safe_w_SQL("UPDATE Finals SET FinTieBreak=UPPER(FinTieBreak), FinTbClosest=1 WHERE FinEvent='{$r->FinEvent}' AND FinMatchNo={$r->FinMatchNo} AND FinTournament={$r->FinTournament}");
    }

    $q=safe_r_SQL("SELECT TfEvent, TfMatchNo, TfTournament, TfTieBreak FROM TeamFinals WHERE ".($ToId ? "TfTournament={$ToId} AND" : "")." BINARY TfTieBreak!=UPPER(TfTieBreak)");
    while($r=safe_fetch($q)) {
        safe_w_SQL("UPDATE TeamFinals SET TfTieBreak=UPPER(TfTieBreak), TfTbClosest=1 WHERE TfEvent='{$r->TfEvent}' AND TfMatchNo={$r->TfMatchNo} AND TfTournament={$r->TfTournament}");
    }

    $q=safe_r_SQL("SELECT ElElimPhase, ElEventCode, ElTournament, ElQualRank, ElTieBreak FROM Eliminations WHERE ".($ToId ? "ElTournament={$ToId} AND" : "")." BINARY ElTieBreak!=UPPER(ElTieBreak)");
    while($r=safe_fetch($q)) {
        safe_w_SQL("UPDATE Eliminations SET ElTieBreak=UPPER(ElTieBreak), ElTbClosest=1 WHERE  ElElimPhase={$r->ElElimPhase} AND ElEventCode='{$r->ElEventCode}' AND ElTournament={$r->ElTournament} AND ElQualRank={$r->ElQualRank}");
    }
}

function updateTbDecoded_20200519($ToId=0)  {
    $q=safe_r_SQL("SELECT IndId, IndEvent, IndTournament, trim(IndTieBreak) as TieString, IndTbClosest as Closest, ifnull(EvElimSO,1) as ElimSO FROM Individuals left join Events on IndEvent=EvCode and EvTeamEvent=0 and EvTournament=IndTournament WHERE ".($ToId ? "IndTournament={$ToId} AND" : "")." trim(IndTieBreak)!=''");
    while($r=safe_fetch($q)) {
        safe_w_SQL("UPDATE Individuals SET IndTbDecoded=".StrSafe_DB(decodeTie($r->TieString, $r->ElimSO, $r->Closest))." WHERE IndId={$r->IndId} AND IndEvent='{$r->IndEvent}' AND IndTournament={$r->IndTournament}");
    }

    $q=safe_r_SQL("SELECT TeCoId, TeSubTeam, TeEvent, TeTournament, TeFinEvent, trim(TeTieBreak) as TieString, TeTbClosest as Closest, ifnull(EvElimSO,3) as ElimSO FROM Teams inner join Events on EvTournament=TeTournament and EvTeamEvent=1 and EvCode=TeEvent WHERE ".($ToId ? "TeTournament={$ToId} AND" : "")." trim(TeTieBreak)!=''");
    while($r=safe_fetch($q)) {
        safe_w_SQL("UPDATE Teams SET TeTbDecoded=".StrSafe_DB(decodeTie($r->TieString, $r->ElimSO, $r->Closest))." WHERE TeCoId={$r->TeCoId} AND TeSubTeam={$r->TeSubTeam} AND TeEvent='{$r->TeEvent}' AND TeTournament={$r->TeTournament} AND TeFinEvent={$r->TeFinEvent}");
    }

    $q=safe_r_SQL("SELECT FinEvent, FinMatchNo, FinTournament, trim(FinTieBreak) as TieString, FinTbClosest as Closest, ifnull(EvElimSO,1) as ElimSO FROM Finals inner join Events on EvTournament=FinTournament and EvTeamEvent=0 and EvCode=FinEvent WHERE ".($ToId ? "FinTournament={$ToId} AND" : "")." trim(FinTieBreak)!=''");
    while($r=safe_fetch($q)) {
        safe_w_SQL("UPDATE Finals SET FinTbDecoded=".StrSafe_DB(decodeTie($r->TieString, $r->ElimSO, $r->Closest))." WHERE FinEvent='{$r->FinEvent}' AND FinMatchNo={$r->FinMatchNo} AND FinTournament={$r->FinTournament}");
    }

    $q=safe_r_SQL("SELECT TfEvent, TfMatchNo, TfTournament, trim(TfTieBreak) as TieString, TfTbClosest as Closest, ifnull(EvElimSO,3) as ElimSO FROM TeamFinals inner join Events on EvTournament=TfTournament and EvTeamEvent=1 and EvCode=TfEvent WHERE ".($ToId ? "TfTournament={$ToId} AND" : "")." trim(TfTieBreak)!=''");
    while($r=safe_fetch($q)) {
        safe_w_SQL("UPDATE TeamFinals SET TfTbDecoded=".StrSafe_DB(decodeTie($r->TieString, $r->ElimSO, $r->Closest))." WHERE TfEvent='{$r->TfEvent}' AND TfMatchNo={$r->TfMatchNo} AND TfTournament={$r->TfTournament}");
    }

    $q=safe_r_SQL("SELECT ElElimPhase, ElEventCode, ElTournament, ElQualRank, trim(ElTieBreak) as TieString, ElTbClosest as Closest, ifnull(EvElimSO,1) as ElimSO FROM Eliminations inner join Events on EvTournament=ElTournament and EvTeamEvent=0 and EvCode=ElEventCode WHERE ".($ToId ? "ElTournament={$ToId} AND" : "")." trim(ElTieBreak)!=''");
    while($r=safe_fetch($q)) {
        safe_w_SQL("UPDATE Eliminations SET ElTbDecoded=".StrSafe_DB(decodeTie($r->TieString, $r->ElimSO, $r->Closest))." WHERE  ElElimPhase={$r->ElElimPhase} AND ElEventCode='{$r->ElEventCode}' AND ElTournament={$r->ElTournament} AND ElQualRank={$r->ElQualRank}");
    }
}

function updateContacts_20210515($ToId=0) {
	if($ToId) {
		$q=safe_r_sql("select ExtraDataCountries.* from ExtraDataCountries inner join Countries on CoId=EdcId and CoTournament=$ToId where EdcType='E' order by EdcId");
	} else {
		$q=safe_r_sql("select * from ExtraDataCountries where EdcType='E' order by EdcId");
	}

	$OldCountry=0;
	$Edc=array();
	while($r=safe_fetch($q)) {
		if(!$r->EdcExtra) {
			continue;
		}
		foreach(unserialize($r->EdcExtra) as $extra) {
			if(empty($extra['EnCode'])) {
				$t=safe_r_sql("select * from Entries where EnFirstName=".StrSafe_DB($extra['FamilyName'])." and EnName=".StrSafe_DB($extra['GivenName']));
				if($u=safe_fetch($t)) {
					$extra['EnCode']=$u->EnCode;
				}
			}
			$extra['Preferred']=($r->EdcEvent=='P');
			$Edc[$r->EdcId][$extra['EnCode']]=$extra;
		}
	}

	// delete the old system and replace with the new
	foreach($Edc as $CoId => $Items) {
		safe_w_sql("delete from ExtraDataCountries where EdcId=$CoId and EdcType='E'");
		safe_w_sql("insert into ExtraDataCountries set EdcId=$CoId, EdcType='E', EdcExtra=".StrSafe_DB(serialize($Items)));
	}
}

function updateTeamFinComponentsLog_20220320($ToId=0) {
    safe_w_SQL("INSERT IGNORE INTO `TeamFinComponentLog` (`TfclCoId`, `TfclSubTeam`, `TfclTournament`, `TfclEvent`, `TfclIdPrev`, `TfclIdNext`, `TfclOrder`, `TfclTimeStamp`)
        SELECT `TcCoId`, `TcSubTeam`, `TcTournament`, `TcEvent`, `TcId`,  `TfcId`, `TcOrder`, `TfcTimeStamp` 
        FROM `TeamComponent`
        INNER JOIN `TeamFinComponent` ON `TcCoId`=`TfcCoId` AND `TcSubTeam`=`TfcSubTeam` AND `TcTournament`=`TfcTournament` AND `TcEvent`= `TfcEvent` AND `TcOrder`=`TfcOrder`
        WHERE ". ($ToId ? "`TcTournament`={$ToId} AND ":"") . "TcFinEvent=1 and `TcId` != `TfcId`");
    $q = safe_r_SQL("SELECT `TfclCoId`, `TfclSubTeam`, `TfclTournament`, `TfclEvent`, `TfclOrder`, `TfclTimeStamp` 
        FROM `TeamFinComponentLog` " . ($ToId ? " WHERE `TfclTournament`={$ToId}" : ""));
    while($r=safe_fetch($q)) {
        safe_w_SQL("INSERT IGNORE INTO `TeamFinComponentLog` (`TfclCoId`, `TfclSubTeam`, `TfclTournament`, `TfclEvent`, `TfclIdPrev`, `TfclIdNext`, `TfclOrder`, `TfclTimeStamp`)
        SELECT DISTINCT `TfcCoId`, `TfcSubTeam`, `TfcTournament`, `TfcEvent`, `TfcId`,  `TfcId`, `TfcOrder`, '{$r->TfclTimeStamp}' 
        FROM `TeamFinComponent`
        WHERE `TfcCoId`={$r->TfclCoId} AND `TfcSubTeam`={$r->TfclSubTeam} AND `TfcTournament`={$r->TfclTournament} AND `TfcEvent`= '{$r->TfclEvent}' AND `TfcOrder`!= {$r->TfclOrder}");
    }
}

function updateArrowstrings_20231116($ToId=0) {
    safe_w_sql("UPDATE `Qualifications` INNER JOIN Entries ON QuId=EnID SET 
        QuD1Arrowstring=REPLACE(QuD1Arrowstring,'R','5'),
        QuD2Arrowstring=REPLACE(QuD2Arrowstring,'R','5'),
        QuD3Arrowstring=REPLACE(QuD3Arrowstring,'R','5'),
        QuD4Arrowstring=REPLACE(QuD4Arrowstring,'R','5'),
        QuD5Arrowstring=REPLACE(QuD5Arrowstring,'R','5'),
        QuD6Arrowstring=REPLACE(QuD6Arrowstring,'R','5'),
        QuD7Arrowstring=REPLACE(QuD7Arrowstring,'R','5'),
        QuD8Arrowstring=REPLACE(QuD8Arrowstring,'R','5') WHERE EnTournament={$ToId}");
    safe_w_sql("UPDATE `Qualifications` INNER JOIN Entries ON QuId=EnID SET
        QuD1Arrowstring=REPLACE(QuD1Arrowstring,'S','6'),
        QuD2Arrowstring=REPLACE(QuD2Arrowstring,'S','6'),
        QuD3Arrowstring=REPLACE(QuD3Arrowstring,'S','6'),
        QuD4Arrowstring=REPLACE(QuD4Arrowstring,'S','6'),
        QuD5Arrowstring=REPLACE(QuD5Arrowstring,'S','6'),
        QuD6Arrowstring=REPLACE(QuD6Arrowstring,'S','6'),
        QuD7Arrowstring=REPLACE(QuD7Arrowstring,'S','6'),
        QuD8Arrowstring=REPLACE(QuD8Arrowstring,'S','6') WHERE EnTournament={$ToId}");
    safe_w_sql("UPDATE `Qualifications` INNER JOIN Entries ON QuId=EnID SET 
        QuD1Arrowstring=REPLACE(QuD1Arrowstring,'T','8'),
        QuD2Arrowstring=REPLACE(QuD2Arrowstring,'T','8'),
        QuD3Arrowstring=REPLACE(QuD3Arrowstring,'T','8'),
        QuD4Arrowstring=REPLACE(QuD4Arrowstring,'T','8'),
        QuD5Arrowstring=REPLACE(QuD5Arrowstring,'T','8'),
        QuD6Arrowstring=REPLACE(QuD6Arrowstring,'T','8'),
        QuD7Arrowstring=REPLACE(QuD7Arrowstring,'T','8'),
        QuD8Arrowstring=REPLACE(QuD8Arrowstring,'T','8') WHERE EnTournament={$ToId}");
    safe_w_sql("UPDATE `Qualifications` INNER JOIN Entries ON QuId=EnID SET 
        QuD1Arrowstring=REPLACE(QuD1Arrowstring,'U','7'),
        QuD2Arrowstring=REPLACE(QuD2Arrowstring,'U','7'),
        QuD3Arrowstring=REPLACE(QuD3Arrowstring,'U','7'),
        QuD4Arrowstring=REPLACE(QuD4Arrowstring,'U','7'),
        QuD5Arrowstring=REPLACE(QuD5Arrowstring,'U','7'),
        QuD6Arrowstring=REPLACE(QuD6Arrowstring,'U','7'),
        QuD7Arrowstring=REPLACE(QuD7Arrowstring,'U','7'),
        QuD8Arrowstring=REPLACE(QuD8Arrowstring,'U','7') WHERE EnTournament={$ToId}");
}

function updateLancaster_20240108($ToId=0) {
    safe_w_sql("UPDATE `Qualifications` 
        inner join Entries on EnId=QuId and EnTournament=$ToId
        inner join Tournament on ToId=EnTournament and ToLocRule='LANC' 
        SET QuD1Arrowstring=REPLACE(QuD1Arrowstring,'X','M'), QuD2Arrowstring=REPLACE(QuD2Arrowstring,'X','M'),
            QuD3Arrowstring=REPLACE(QuD3Arrowstring,'X','M'), QuD4Arrowstring=REPLACE(QuD4Arrowstring,'X','M'),
            QuD5Arrowstring=REPLACE(QuD5Arrowstring,'X','M'), QuD6Arrowstring=REPLACE(QuD6Arrowstring,'X','M'),
            QuD7Arrowstring=REPLACE(QuD7Arrowstring,'X','M'), QuD8Arrowstring=REPLACE(QuD8Arrowstring,'X','M')");
    safe_w_sql("UPDATE `Finals` inner join Tournament on ToId=FinTournament and ToLocRule='LANC' SET FinArrowstring=REPLACE(FinArrowstring,'X','M') where FinTournament=$ToId");
    safe_w_sql("UPDATE RoundRobinMatches inner join Tournament on ToId=RrMatchTournament and ToLocRule='LANC' SET RrMatchArrowstring=REPLACE(RrMatchArrowstring,'X','M') where RrMatchTournament=$ToId");
    safe_w_sql("update Tournament set ToGolds='11', ToGoldsChars='M' where ToId=$ToId and ToLocRule='LANC'");
    safe_w_sql("update Events inner join Tournament on ToId=EvTournament and ToLocRule='LANC' set EvFinalTargetType=if(EvFinalTargetType=24,EvFinalTargetType,25), EvGolds='11', EvGoldsChars='M' where EvTournament=$ToId");
    safe_w_sql("update TargetFaces inner join Tournament on ToId=TfTournament and ToLocRule='LANC' set TfT1=25, TfT2=25, TfGolds='11', TfGoldsChars='M' where TfTournament=$ToId");
}

function updateSevereBug_20240114($ToId) {
    safe_w_sql("UPDATE `TargetFaces` 
        inner join `Tournament` on ToId=TfTournament and ToLocRule!='LANC'
        SET TfGolds='', TfGoldsChars=''
        WHERE  TfTournament=$ToId AND `TfGolds` LIKE '11' AND `TfXNine` = '' AND `TfGoldsChars` LIKE 'M' AND `TfXNineChars` = ''",false,array(1146, 1060));
}

function updateTournamentInvolvedTS_20240826($ToId) {
    safe_w_sql("UPDATE `TournamentInvolved` 
        INNER JOIN `Tournament` ON `TiTournament`=`ToId` 
        SET `TiTimeStamp`=`ToWhenFrom` 
        WHERE `ToId`=$ToId AND `TiTimeStamp`='0000-00-00'");
}

function updateAclTemplates_20240920($ToId) {
    safe_w_sql("update `AclDetails` set `AclDtFeature`=22 where `AclDtFeature`=9 AND `AclDtTournament`=$ToId");
    safe_w_sql("update `AclDetails` set `AclDtFeature`=23 where `AclDtFeature`=2 AND `AclDtTournament`=$ToId");
    safe_w_sql("update `AclDetails` set `AclDtFeature`=24 where `AclDtFeature`=7 AND `AclDtTournament`=$ToId");
    safe_w_sql("update `AclDetails` set `AclDtFeature`=25 where `AclDtFeature`=3 AND `AclDtTournament`=$ToId");
    safe_w_sql("update `AclDetails` set `AclDtFeature`=26 where `AclDtFeature`=4 AND `AclDtTournament`=$ToId");
    safe_w_sql("update `AclDetails` set `AclDtFeature`=27 where `AclDtFeature`=16 AND `AclDtTournament`=$ToId");
    safe_w_sql("update `AclDetails` set `AclDtFeature`=28 where `AclDtFeature`=5 AND `AclDtTournament`=$ToId");
    safe_w_sql("update `AclDetails` set `AclDtFeature`=29 where `AclDtFeature`=6 AND `AclDtTournament`=$ToId");
    safe_w_sql("update `AclDetails` set `AclDtFeature`=30 where `AclDtFeature`=13 AND `AclDtTournament`=$ToId");
    safe_w_sql("update `AclDetails` set `AclDtFeature`=31 where `AclDtFeature`=14 AND `AclDtTournament`=$ToId");
    safe_w_sql("update `AclDetails` set `AclDtFeature`=32 where `AclDtFeature`=11 AND `AclDtTournament`=$ToId");
    safe_w_sql("update `AclDetails` set `AclDtFeature`=33 where `AclDtFeature`=12 AND `AclDtTournament`=$ToId");
    safe_w_sql("update `AclDetails` set `AclDtFeature`=34 where `AclDtFeature`=8 AND `AclDtTournament`=$ToId");
    safe_w_sql("update `AclDetails` set `AclDtFeature`=35 where `AclDtFeature`=10 AND `AclDtTournament`=$ToId");
    safe_w_sql("update `AclDetails` set `AclDtFeature`=36 where `AclDtFeature`=15 AND `AclDtTournament`=$ToId");
    safe_w_sql("UPDATE `AclDetails` set `AclDtFeature`=`AclDtFeature`-20 where `AclDtFeature`>20 AND `AclDtTournament`=$ToId");

    safe_w_sql("INSERT IGNORE INTO AclTemplates (`AclTeTournament`, `AclTePattern`, `AclTeNick`, `AclTeFeatures`, `AclTeEnabled`)
        SELECT `AclTournament`, `AclIP`, `AclNick`, GROUP_CONCAT(CONCAT_WS('|', AclDtFeature, AclDtSubFeature, AclDtLevel) ORDER BY AclDtFeature, AclDtSubFeature SEPARATOR '#') as `Features`, AclEnabled
        FROM `ACL` 
        INNER JOIN AclDetails on AclTournament=AclDTTournament and AclIP=AclDtIP
        WHERE `AclTournament`=$ToId AND `AclIP` LIKE '%*%'  
        GROUP BY  `AclTournament`, `AclIP`",false,array(1146, 1060));
    safe_w_sql("DELETE FROM `ACL` WHERE `AclTournament`=$ToId AND `AclIP` LIKE '%*%'",false,array(1146, 1060));
    safe_w_sql("INSERT IGNORE INTO AclTemplates (`AclTeTournament`, `AclTePattern`, `AclTeNick`, `AclTeFeatures`, `AclTeEnabled`)
        SELECT `AclTournament`, `AclNick`, 'REGEXP', GROUP_CONCAT(CONCAT_WS('|', AclDtFeature, AclDtSubFeature, AclDtLevel) ORDER BY AclDtFeature, AclDtSubFeature SEPARATOR '#') as `Features`, AclEnabled
        FROM `ACL` 
        INNER JOIN AclDetails on AclTournament=AclDTTournament and AclIP=AclDtIP
        WHERE `AclTournament`=$ToId AND `AclIP` LIKE '0.0.0.%'
        GROUP BY  `AclTournament`, `AclIP`",false,array(1146, 1060));
    safe_w_sql("DELETE FROM `ACL` WHERE `AclTournament`=$ToId AND `AclIP` LIKE '0.0.0.%'",false,array(1146, 1060));
}