<?php

require_once(dirname(dirname(__FILE__)) . '/config.php');
require_once('Common/pdf/LabelPDF.inc.php');
require_once('Common/Lib/CommonLib.php');

if(isset($_REQUEST['ToCode'])) {
    $_REQUEST['ToId'] = getIdFromCode($_REQUEST['ToCode']);
}
if(!empty($_REQUEST['ToId'])) {
	CreateTourSession(intval($_REQUEST['ToId']));
}

if($Mem=getSystemMemInfo() and !empty($Mem['MemFree'])) {
	$Free=ceil(intval($Mem['MemFree'])/1500000);
	if($Free>3) {
		ini_set('memory_limit',$Free.'G');
	}
}

$CardType=(empty($_REQUEST['CardType']) ? 'A' : $_REQUEST['CardType']);
$CardNumber=(empty($_REQUEST['CardNumber']) ? 0 : intval($_REQUEST['CardNumber']));
$AvailableFonts=getFonts();

$SpecialFilter='';
// $SpecialFilter=' and EnCode in (
// 23717,
// 		27088,
// 		25727
// )';

if(!empty($_REQUEST['SortByTarget'])) {
	switch($CardType) {
		case 'E':
			$SORT='ElTargetNo';
			break;
		default:
			$SORT='QuTargetNo, FirstName, Name';
	}
	$SORT.=', NationCode, FirstName, Name';
}

require_once('CommonCard.php');

$Rs=safe_r_sql($MyQuery);
if (!safe_num_rows($Rs)) {
	include('Common/Templates/head-popup.php');
	echo '<table style="margin-top:20vh" width="100%"><tr><td>';
	echo '<div align="center">' . get_text('BadgeNoData', 'Tournament') . '';
	echo '<br/><br/><input type="button" onclick="window.close();" value="' . get_text('Close') . '">';
	echo '</td></tr></table>';
	include('Common/Templates/tail-popup.php');
	die();
}

$q=safe_r_SQL("select * from IdCards where IcTournament in ($TourId) and IcType='$CardType' and IcNumber=$CardNumber");
if(!($BackGround=safe_fetch($q))) {
	include('Common/Templates/head-popup.php');
	echo '<table height="'.($_SESSION['WINHEIGHT']-50).'" width="100%"><tr><td>';
	echo '<div align="center">' . get_text('BadgeNoData', 'Tournament') . '';
	echo '<br/><br/><input type="button" onclick="window.close();" value="' . get_text('Close') . '">';
	echo '</td></tr></table>';
	include('Common/Templates/tail-popup.php');
	die();
}

// set to -1 the AEOperation for all the entries selected from the query arriving from remote IP...
$RemoteIP=($_SERVER['REMOTE_ADDR']!='::1' ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1');
$Operation=($CardType=='A' ? '-1' : '-1*('.(ord($CardType)*100).'+IcNumber)');

if(!empty($_REQUEST['CardType'])) {
	safe_w_sql("delete from AccEntries where AEOperation<0 and AETournament in ($TourId) and (AEFromIp=INET_ATON('$RemoteIP') or AEId in (select EnId from ($MyQuery) a ))");
	safe_w_sql("insert into AccEntries (AEId, AEExtra, AEOperation, AETournament, AEFromIp) select EnId, ExtraCode, $Operation, EnTournament, INET_ATON('$RemoteIP') from ($MyQuery) a
		on duplicate key update AEFromIp=INET_ATON('$RemoteIP')");
}

$AcTransport=array();
$AcTransport['img']=array('', 'car', 'van', 'bus');
$Just=array('L', 'C', 'R');

$cntPass=0;
$pdf=new LabelPDF();
$pdf->SetCellPadding(0);

// COMMENT when done!!
// Adds some more fonts
//$Path=$CFG->DOCUMENT_PATH.'/Common/tcpdf/fonts/';
//TCPDF_FONTS::addTTFfont($Path.'timesbi.ttf');
//TCPDF_FONTS::addTTFfont($Path.'timesi.ttf');
//TCPDF_FONTS::addTTFfont($Path.'times.ttf');



$CurrentTour=0;
$CurrentBadge=0;

$BadgeElements=array();

while ($MyRow=safe_fetch($Rs)) {
	set_time_limit(30);
	if($MyRow->EnTournament and ($CurrentTour!=$MyRow->EnTournament or $CurrentBadge!=$MyRow->IcNumber)) {
		$CurrentCode=$MyRow->ToCode;

		// get the background of the card
		$q=safe_r_SQL("select * from IdCards where IcTournament={$MyRow->EnTournament} and IcType='$CardType' and IcNumber=$MyRow->IcNumber");
		if(!($BackGround=safe_fetch($q))) {
			continue;
		}
		$BackGround->Options=unserialize($BackGround->IcSettings);

		$Badges=array();

		$OffsetX=explode(';', $BackGround->Options['OffsetX']);
		$OffsetY=explode(';', $BackGround->Options['OffsetY']);

		$Format=array($BackGround->Options['PaperWidth'], $BackGround->Options['PaperHeight']);
		$Orientation=($BackGround->Options['PaperWidth'] > $BackGround->Options['PaperHeight'] ? 'L' : 'P');

		foreach($OffsetY as $y) {
			foreach($OffsetX as $x) {
				$Badges[]=array($x, $y);
			}
		}

		$BadgePerPage=count($Badges);

		$RndImages=array();
		$q=safe_r_sql("select * from IdCardElements where IceTournament={$MyRow->EnTournament} and IceCardType='$CardType' and IceCardNumber=$MyRow->IcNumber and IceType='RandomImage' order by IceOrder");
		while($r=safe_fetch($q)) {
			$r->Options=unserialize($r->IceOptions);
			$RndImages[]=$r;
		}

		$Elements=array();
		$q=safe_r_SQL("select * from IdCardElements where IceTournament={$MyRow->EnTournament} and IceCardType='$CardType' and IceCardNumber=$MyRow->IcNumber and IceType!='RandomImage' order by IceOrder");
		while($r=safe_fetch($q)) {
			$r->Options=unserialize($r->IceOptions);
			if(!empty($r->Options['Font'])) {
				$r->Options['FontStyle']='';
                $FontFile=$AvailableFonts[$r->Options['Font']]['file'] ?? $AvailableFonts[$r->Options['Font'].'.ttf']['file'] ?? K_PATH_FONTS."arial.ttf";
                if(($AvailableFonts[$r->Options['Font']]['type']??'')=='E') {
                    $r->Options['FontFamily'] = TCPDF_FONTS::addTTFfont($FontFile, '', '', 32, dirname($AvailableFonts[$r->Options['Font']]['file']).'/');
                    $pdf->AddFont($r->Options['FontFamily'], '', dirname($AvailableFonts[$r->Options['Font']]['file']).'/'.$r->Options['FontFamily']);
                } else {
                    $r->Options['FontFamily'] = TCPDF_FONTS::addTTFfont($FontFile);
                }
				if(substr($r->Options['Font'],-1)=='i') {
					$r->Options['FontStyle']='I';
					$r->Options['FontFamily']=substr($r->Options['Font'],0,-1);
				}
				if(substr($r->Options['Font'],-1)=='b') {
					$r->Options['FontStyle']='B';
					$r->Options['FontFamily']=substr($r->Options['Font'],0,-1);
				}

		// 		$r->Options['FontFamily']=$pdf->addTTFfont(K_PATH_FONTS.'HelveticaCondensed.ttf');
		//		$r->Options['FontStyle']=(substr($r->Options['Font'], -2, 1)=='b' ? 'B' : '')
		//			.(substr($r->Options['Font'], -1, 1)=='i' ? 'I' : '');
			}
			$Elements[]=$r;
		}

		$cntPass=0;
		$CurrentTour=$MyRow->EnTournament;
		$CurrentBadge=$MyRow->IcNumber;
	}

    if(empty($Badges)) {
        continue;
    }

	$pdf->SetDefaultColor();

	if($cntPass==0) {
		$tmp=$pdf->addPage($Orientation, $Format);
	}

	$StartX=$Badges[$cntPass][0];
	$StartY=$Badges[$cntPass][1];

	$FileExtra="{$CardType}-{$MyRow->IcNumber}";
	if($BackGround->IcBackground and file_exists($Back=$CFG->DOCUMENT_PATH.'TV/Photos/'.$CurrentCode.'-'.$FileExtra.'-Accreditation.jpg')) {
// 		unset($BackGround->IcBackground);
		$ElX=$StartX+$BackGround->Options['IdBgX'];
		$ElY=$StartY+$BackGround->Options['IdBgY'];
		$pdf->Image($Back, $ElX, $ElY, floatval($BackGround->Options['IdBgW']), floatval($BackGround->Options['IdBgH']));
	}

	if($RndImages) {
		$NumRnd=count($RndImages);
		if($MyRow->TargetNo) {
			$Index=(intval($MyRow->TargetNo)*($NumRnd+1)+intval(ord(substr($MyRow->TargetNo, -1))))%$NumRnd;
		} else {
			$Index=hexdec(preg_replace('/[^a-f0-9]/sim', '', $MyRow->Bib))%$NumRnd;
		}
		$Element=$RndImages[$Index];
		$ElX=$StartX+$Element->Options['X'];
		$ElY=$StartY+$Element->Options['Y'];
		if(file_exists($im=$CFG->DOCUMENT_PATH.'TV/Photos/'.$CurrentCode.'-RandomImage-'.$FileExtra.'-'.$Element->IceOrder.'.jpg')) {
			$pdf->Image($im, $ElX, $ElY, floatval($Element->Options['W']), floatval($Element->Options['H']));
		}
	}

	foreach($Elements as $Element) {
		unset($Text);
		$ElX=$StartX+($Element->Options['X']??0);
		$ElY=$StartY+($Element->Options['Y']??0);
		$ElH=($Element->Options['H']??0);

		switch($Element->IceType) {
			case 'ToLeft':
			case 'ToRight':
			case 'ToBottom':
				if(file_exists($im=$CFG->DOCUMENT_PATH.'TV/Photos/'.$CurrentCode.'-'.$Element->IceType.'.jpg')) {
					$pdf->Image($im, $ElX, $ElY, floatval($Element->Options['W']), floatval($Element->Options['H']));
				}
				break;
			case 'Picture':
				if(file_exists($im=$CFG->DOCUMENT_PATH.'TV/Photos/'.$CurrentCode.'-En-'.$MyRow->EnId.'.jpg')) {
					$pdf->Image($im, $ElX, $ElY, floatval($Element->Options['W']), floatval($Element->Options['H']));
				}
				break;
			case 'ImageSvg':
				if(file_exists($im=$CFG->DOCUMENT_PATH.'TV/Photos/'.$CurrentCode.'-ImageSvg-'.$FileExtra.'-'.$Element->IceOrder.'.svg')) {
					$pdf->ImageSVG($im, $ElX, $ElY, floatval($Element->Options['W']), floatval($Element->Options['H']));
				}
				break;
			case 'Image':
				if(file_exists($im=$CFG->DOCUMENT_PATH.'TV/Photos/'.$CurrentCode.'-Image-'.$FileExtra.'-'.$Element->IceOrder.'.jpg')) {
					$pdf->Image($im, $ElX, $ElY, floatval($Element->Options['W']), floatval($Element->Options['H']));
				}
				break;
            case 'WRankImage':
                if(($Element->Options['WRank'] == 0 OR $Element->Options['WRank']>=$MyRow->WRank) AND !empty($MyRow->WRank)) {
                    if (file_exists($im = $CFG->DOCUMENT_PATH . 'TV/Photos/' . $CurrentCode . '-WRankImage-' . $FileExtra . '-' . $Element->IceOrder . '.jpg')) {
                        $pdf->Image($im, $ElX, $ElY, floatval($Element->Options['W']), floatval($Element->Options['H']));
                    }
                }
                break;
            case 'ExtraAddOnsImage':
                $listAddOns = getModuleParameter("ExtraAddOns","AddOnsList", array());
                if(($MyRow->ExtraAddOns & pow(2,$Element->Options['ExtraAddOns']))!=0 AND array_key_exists($Element->Options['ExtraAddOns'], $listAddOns) AND !empty($listAddOns[$Element->Options['ExtraAddOns']])) {
                    if (file_exists($im = $CFG->DOCUMENT_PATH . 'TV/Photos/' . $CurrentCode . '-ExtraAddOnsImage-' . $FileExtra . '-' . $Element->IceOrder . '.jpg')) {
                        $pdf->Image($im, $ElX, $ElY, floatval($Element->Options['W']), floatval($Element->Options['H']));
                    }
                }
                break;
			case 'Accomodation':
				$Type='B';
				$Fill=false;
				if(!empty($Element->Options['BackCat'])) {
					$R=hexdec(substr($MyRow->AcColor, 0, 2));
					$G=hexdec(substr($MyRow->AcColor, 2, 2));
					$B=hexdec(substr($MyRow->AcColor, 4, 2));
					$pdf->SetFillColor($R, $G, $B);
					$Fill=true;
					if(IsDarkBackground(array($R, $G, $B))) $Type='W';
				} elseif($Element->Options['BackCol']) {
					$R=hexdec(substr($Element->Options['BackCol'], 1, 2));
					$G=hexdec(substr($Element->Options['BackCol'], 3, 2));
					$B=hexdec(substr($Element->Options['BackCol'], 5, 2));
					$pdf->SetFillColor($R, $G, $B);
					$Fill=true;
					if(IsDarkBackground(array($R, $G, $B))) $Type='W';
				} elseif($Element->Options['Col']=="#FFFFFF") {
					$Type='W';
				} else {
					$Type='B';
				}
				if(!empty($Element->Options['BackCat']) and $MyRow->AcTitleReverse) {
					$pdf->setColor('text', 255, 255, 255);
				} elseif($Element->Options['Col']) {
					$pdf->setColor('text', hexdec(substr($Element->Options['Col'], 1, 2)), hexdec(substr($Element->Options['Col'], 3, 2)), hexdec(substr($Element->Options['Col'], 5, 2)));
				} else {
					$pdf->setColor('text', 0, 0, 0);
				}
				$pdf->SetXY($ElX, $ElY );
				$pdf->Cell($Element->Options['W'], $Element->Options['H'], '', '', true,
						'', $Fill);

				$AcX=$ElX;
				if($MyRow->AcMeal) {
					$pdf->ImageSVG($CFG->DOCUMENT_PATH . 'Common/Images/eat-'.$Type.'.svg',
						$AcX, $ElY, 0, floatval($Element->Options['H']), '', 'T');
					$AcX=$pdf->getx()+2;
				}
				if($MyRow->AcAccomodation) {
					$pdf->ImageSVG($CFG->DOCUMENT_PATH . 'Common/Images/bed-'.$Type.'.svg',
						$AcX, $ElY, 0, floatval($Element->Options['H']), '', 'T');
					$AcX=$pdf->getx()+2;
				}
				if($MyRow->AcTransport) {
					$pdf->ImageSVG($CFG->DOCUMENT_PATH . 'Common/Images/' . $AcTransport['img'][$MyRow->AcTransport] . '-'.$Type.'.svg',
						$AcX, $ElY, 0, floatval($Element->Options['H']));
				}
				break;
// 		if($MyRow->AcAccomodation != 0)
// 			$pdf->Image($CFG->DOCUMENT_PATH . 'Common/Images/AcAccomodation.png', $PosX+($AccW*0.165)+4, $PosY+($AccH*0.81), $AccW*0.0825, 0, 'png');
// 		if($MyRow->AcMeal != 0)
// 			$pdf->Image($CFG->DOCUMENT_PATH . 'Common/Images/AcMeal.png', $PosX+($AccW*0.2475)+6, $PosY+($AccH*0.81), $AccW*0.0675, 0, 'png');
				// 				[AcTransport] => 3
// 				[AcAccomodation] => 1
// 				[AcMeal] => 1
			case 'Flag':
				if(file_exists($im=$CFG->DOCUMENT_PATH.'TV/Photos/'.$CurrentCode.'-FlSvg-'.$MyRow->NationCode.'.svg')) {
					$pdf->ImageSVG($im, max(0.15, $ElX), $ElY, floatval($Element->Options['W']), floatval($Element->Options['H']));
					$pdf->SetDrawColor(128);
					$pdf->Rect(max(0.15, $ElX), $ElY, floatval($Element->Options['W']), floatval($Element->Options['H']));
					$pdf->SetDrawColor(0);
				} elseif(file_exists($im=$CFG->DOCUMENT_PATH.'TV/Photos/'.$CurrentCode.'-Fl-'.$MyRow->NationCode.'.jpg')) {
					$pdf->Image($im, max(0.15, $ElX), $ElY, floatval($Element->Options['W']), floatval($Element->Options['H']));
					$pdf->SetDrawColor(128);
					$pdf->Rect(max(0.15, $ElX), $ElY, floatval($Element->Options['W']), floatval($Element->Options['H']));
					$pdf->SetDrawColor(0);
				}
				break;
            case 'AccessGraphics' :
                $offsetAccess = 0;
                $NumElements=0;
                for ($i = 0; $i < 8; $i++) {
	                if ($MyRow->{'AcArea' . $i}) {
		                $NumElements++;
	                }
                }
                switch($Element->Options['Just']) {
	                case '1': // centered
						$offsetAccess=(($Element->Options['W'])-((($Element->Options['H']+0.8)*$NumElements) + ($NumElements-1)*1.5))/2;
		                break;
                }
                for ($i = 0; $i < 8; $i++) {
                    if ($MyRow->{'AcArea' . $i}) {

                    	// defaults
	                    //$Element->Options['FontFamily']='helveticaneueltprocn';
	                    //$Element->Options['FontStyle']='';
	                    //$Element->Options['Size']=13;
	                    //$Element->Options['BackCol']='';
	                    //$Element->Options['Col']='#bfbfbf';
	                    //$Element->Options['Just']='1';
                        if(strpos($Element->Options['Size'],'-')!==false) {
                            list($fSize,$fSpace) = explode('-',$Element->Options['Size']);
                            $Element->Options['Size'] = $fSize;
                            $pdf->setFontSpacing($fSpace);
                        } else {
                            $pdf->setFontSpacing(0);
                        }
	                    $pdf->SetFont($Element->Options['FontFamily'], $Element->Options['FontStyle'], $Element->Options['Size']);
	                    $Fill=false;
	                    $ReverseText=false;
	                    if(!empty($Element->Options['BackCat'])) {
		                    $R=hexdec(substr($MyRow->AcColor, 0, 2));
		                    $G=hexdec(substr($MyRow->AcColor, 2, 2));
		                    $B=hexdec(substr($MyRow->AcColor, 4, 2));
		                    $Fill=array($R, $G, $B);
		                    if(IsDarkBackground(array($R, $G, $B))) $ReverseText=true;
	                    } elseif($Element->Options['BackCol']) {
		                    $R=hexdec(substr($Element->Options['BackCol'], 1, 2));
		                    $G=hexdec(substr($Element->Options['BackCol'], 3, 2));
		                    $B=hexdec(substr($Element->Options['BackCol'], 5, 2));
		                    $pdf->SetFillColor($R, $G, $B);
		                    $Fill=array($R, $G, $B);
		                    if(IsDarkBackground(array($R, $G, $B))) $ReverseText=true;
	                    }

	                    if(!empty($Element->Options['BackCat']) and $ReverseText) {
		                    $pdf->setColor('text', 255, 255, 255);
	                    } elseif($Element->Options['Col']) {
		                    $R=hexdec(substr($Element->Options['Col'], 1, 2));
		                    $G=hexdec(substr($Element->Options['Col'], 3, 2));
		                    $B=hexdec(substr($Element->Options['Col'], 5, 2));
		                    if(IsDarkBackground(array($R, $G, $B)) and $ReverseText) {
			                    $pdf->setColor('text', 255, 255, 255);
			                    $pdf->setColor('draw', 255, 255, 255);
		                    } else {
			                    $pdf->setColor('text', $R, $G, $B);
			                    $pdf->setColor('draw', $R, $G, $B);
		                    }
	                    } else {
		                    $pdf->setColor('text', 0, 0, 0);
		                    $pdf->setColor('draw', 0, 0, 0);
	                    }

                    	$pdf->SetXY($ElX + $offsetAccess, $ElY);
                    	$oldWidth=$pdf->GetLineWidth();
                    	if($Fill) {
		                    $pdf->RoundedRect($ElX + $offsetAccess, $ElY, $Element->Options['H'], $Element->Options['H'], $Element->Options['H']*0.12, '1111', 'B', array('width' => 0.4), $Fill);
	                    } else {
		                    $pdf->RoundedRect($ElX + $offsetAccess, $ElY, $Element->Options['H'], $Element->Options['H'], $Element->Options['H']*0.12, '1111', '', array('width' => 0.4));
	                    }
	                    $pdf->Cell($Element->Options['H'], $Element->Options['H'], $i.(($i < 2 and $MyRow->AcAreaStar) ? '*' : ''), 0, true,
		                    $Just[$Element->Options['Just']], '', '', 1, false, 'T', 'B');

	                    if($Fill) $pdf->SetCellPadding(0);
	                    $pdf->SetLineWidth($oldWidth);

                        //$im = $CFG->DOCUMENT_PATH . '/Common/Images/Access' . $i . (($i < 2 and $MyRow->AcAreaStar) ? 's' : '') . '.png';
                        //$pdf->Image($im, $ElX + $offsetAccess, $ElY, $Element->Options['H'], $Element->Options['H']);
                        $offsetAccess += $Element->Options['H'] + 1.5;
                    }
                }
                break;
			case 'ColoredArea':
				$Text=array();
				foreach(explode("\n", $Element->IceContent) as $l) {
					$Text[]=trim($l);
				}

				$ElH=($Element->Options['H']??0)/count($Text);
			case 'CompName':
				if(!isset($Text)) $Text=array($MyRow->ToName);
			case 'CompDetails':
				if(!isset($Text)) $Text=array(preg_replace("/\s+/", ' ', $MyRow->ToWhere).' - '.TournamentDate2StringShort($MyRow->ToWhenFrom, $MyRow->ToWhenTo));
			case 'TeamComponents':
				if(!isset($Text)) {
					switch($Element->IceContent) {
						case 'OneLine': $Text=array(str_replace('|', ' - ', $MyRow->TeamComponents)); break;
						case 'MultiLine': $Text=explode('|', $MyRow->TeamComponents); break;
					}
				}
			case 'AthCode':
				if(!isset($Text)) $Text=array($MyRow->Bib);
			case 'Athlete':
				if(!isset($Text)) {
					switch($Element->IceContent) {
						case 'FamCaps': $Text=array($MyRow->FamCaps); break;
						case 'FamCaps-GAlone': $Text=array($MyRow->FamCaps.' '.substr($MyRow->GivCaps, 0, 1).'.'); break;
						case 'FamCaps-GivCamel': $Text=array($MyRow->FamCaps.' '.$MyRow->GivCamel); break;
						case 'FamCaps-GivCaps': $Text=array($MyRow->FamCaps.' '.$MyRow->GivCaps); break;
                        case 'FamCaps-GivCaps-ClubCaps': $Text=array($MyRow->FamCaps.' '.$MyRow->GivCaps.', '.$MyRow->NationCaps); break;
						case 'FamCamel': $Text=array($MyRow->FamCamel); break;
						case 'FamCamel-GAlone': $Text=array($MyRow->FamCamel.' '.substr($MyRow->GivCaps, 0, 1).'.'); break;
						case 'FamCamel-GivCamel': $Text=array($MyRow->FamCamel.' '.$MyRow->GivCamel); break;
                        case 'FamCaps-GivCamel-ClubCamel': $Text=array($MyRow->FamCamel.' '.$MyRow->GivCamel.', '.$MyRow->Nation); break;
                        case 'FamCaps-GivCamel-ClubCaps': $Text=array($MyRow->FamCamel.' '.$MyRow->GivCamel.', '.$MyRow->NationCaps); break;
						case 'GivCamel': $Text=array($MyRow->GivCamel); break;
						case 'GivCamel-FamCamel': $Text=array($MyRow->GivCamel.' '.$Text=$MyRow->FamCamel); break;
                        case 'GivCamel-FamCamel-ClubCamel': $Text=array($MyRow->GivCamel.' '.$Text=$MyRow->FamCamel.', '.$MyRow->Nation); break;
						case 'GivCamel-FamCaps': $Text=array($MyRow->GivCamel.' '.$MyRow->FamCaps); break;
                        case 'GivCamel-FamCaps-ClubCamel': $Text=array($MyRow->GivCamel.' '.$MyRow->FamCaps.', '.$MyRow->Nation); break;
                        case 'GivCamel-FamCaps-ClubCaps': $Text=array($MyRow->GivCamel.' '.$MyRow->FamCaps.', '.$MyRow->NationCaps); break;
						case 'GivCaps': $Text=array($MyRow->GivCaps); break;
						case 'GivCaps-FamCaps': $Text=array($MyRow->GivCaps.' '.$MyRow->FamCaps); break;
                        case 'GivCaps-FamCaps-ClubCaps': $Text=array($MyRow->GivCaps.' '.$MyRow->FamCaps.', '.$MyRow->NationCaps); break;
						case 'GAlone-FamCaps': $Text=array(substr($MyRow->GivCaps, 0, 1).'. '.$MyRow->FamCaps); break;
						case 'GAlone-FamCamel': $Text=array(substr($MyRow->GivCaps, 0, 1).'.'); break;
					}
				}
			case 'Club':
				if(!isset($Text)) {
					switch($Element->IceContent) {
						case 'NocCaps-ClubCamel':$Text=array($MyRow->NationCode.' '.$MyRow->Nation); break;
						case 'NocCaps-ClubCaps':$Text=array($MyRow->NationCode.' '.$MyRow->NationCaps); break;
						case 'NocCaps':$Text=array($MyRow->NationCode); break;
						case 'ClubCamel':$Text=array($MyRow->Nation); break;
						case 'ClubCaps':$Text=array($MyRow->NationCaps); break;
					}
				}
            case 'Club2':
                if(!isset($Text)) {
                    if($MyRow->CoId2 != 0) {
                        switch ($Element->IceContent) {
                            case 'NocCaps-ClubCamel':
                                $Text = array($MyRow->NationCode2 . ' ' . $MyRow->Nation2);
                                break;
                            case 'NocCaps-ClubCaps':
                                $Text = array($MyRow->NationCode2 . ' ' . $MyRow->NationCaps2);
                                break;
                            case 'NocCaps':
                                $Text = array($MyRow->NationCode2);
                                break;
                            case 'ClubCamel':
                                $Text = array($MyRow->Nation2);
                                break;
                            case 'ClubCaps':
                                $Text = array($MyRow->NationCaps2);
                                break;
                        }
                    } else {
                        $Text = array();
                    }
                }
            case 'Club3':
                if(!isset($Text)) {
                    if($MyRow->CoId3 != 0) {
                        switch ($Element->IceContent) {
                            case 'NocCaps-ClubCamel':
                                $Text = array($MyRow->NationCode3 . ' ' . $MyRow->Nation3);
                                break;
                            case 'NocCaps-ClubCaps':
                                $Text = array($MyRow->NationCode3 . ' ' . $MyRow->NationCaps3);
                                break;
                            case 'NocCaps':
                                $Text = array($MyRow->NationCode3);
                                break;
                            case 'ClubCamel':
                                $Text = array($MyRow->Nation3);
                                break;
                            case 'ClubCaps':
                                $Text = array($MyRow->NationCaps3);
                                break;
                        }
                    } else {
                        $Text = array();
                    }
                }
			case 'Category':
				if(!isset($Text)) {
					$Code=$MyRow->DivCode.$MyRow->ClassCode.' - ';
					switch($Element->IceContent) {
						case 'CatCode':$Text=array($MyRow->DivCode.$MyRow->ClassCode); break;
						case 'CatDescr':
                        case 'CatDescrUpper':
                            $Code='';
						case 'CatCode-EvDescr':
							if($MyRow->EnCaption) {
								$T=$MyRow->EnCaption;
							} else {
								$T=($MyRow->AcIsAthlete ? $MyRow->DivDescription. ' ' : '').$MyRow->ClDescription;
							}
                            if ($Element->IceContent == 'CatDescrUpper') {
                                $T = mb_convert_case($T, MB_CASE_UPPER, "UTF-8");
                            }
							$Text=array($Code.$T);
							break;
						default:
							if($MyRow->EnCaption) {
								$T=$MyRow->EnCaption;
							} else {
								$T=($MyRow->AcIsAthlete ? $MyRow->DivDescription. ' ' : '').$MyRow->ClDescription;
							}
							$Text=array($T);
					}
				}
            case 'QRScore':
                if(!isset($Text)) {
                    $Text=array($MyRow->QRScore);
                }
			case 'Ranking':
				if(!isset($Text)) {
                    switch($Element->IceContent) {
                        case '':
                        case 'Cardinal':  $Text=array($MyRow->Rank); break;
                        case 'Ordinal':  $Text=array(ordinal($MyRow->Rank)); break;
						case 'Roman': $Text=array(inttoRoman($MyRow->Rank)); break;
                    }
                }
			case 'FinalRanking':
                if(!isset($Text)) {
                    switch($Element->IceContent) {
                        case '':
                        case 'Cardinal':  $Text=array($MyRow->RankFinal); break;
                        case 'Ordinal':  $Text=array(ordinal($MyRow->RankFinal)); break;
						case 'Roman': $Text=array(inttoRoman($MyRow->RankFinal)); break;
                    }
                }
            case 'WRank':
                if(!isset($Text)) {
                    if(($Element->IceContent == 0 OR $Element->IceContent>=$MyRow->WRank) AND !empty($MyRow->WRank)) {
                        $Text = array($MyRow->WRank);
                    } else {
                        $Text = array('');
                    }
                }
            case 'ExtraAddOns':
                if(!isset($Text)) {
                    $listAddOns = getModuleParameter("ExtraAddOns","AddOnsList", array());
                    if(($MyRow->ExtraAddOns & pow(2,$Element->IceContent))!=0 AND array_key_exists($Element->IceContent, $listAddOns) AND !empty($listAddOns[$Element->IceContent])) {
                        $Text = array($listAddOns[$Element->IceContent]);
                    } else {
                        $Text = array();
                    }
                }
			case 'Event':
				if(!isset($Text)) {
					$Text=array($MyRow->EvCode);
					switch($Element->IceContent) {
						case 'EvCode':$Text=array($MyRow->EvCode); break;
						case 'EvCode-EvDescr':$Text=array($MyRow->EvCode. ' '.$MyRow->EvEventName); break;
						case 'EvDescr':$Text=array($MyRow->EvEventName); break;
					}
				}
			case 'Session':
				if(!isset($Text)) {
					if($MyRow->SesName) {
						$Text=array($MyRow->SesName);
					} elseif($MyRow->Session) {
						$Text=array(get_text('Session') . ' ' . $MyRow->Session);
					} else {
						$Text=array('');
					}
				}
			case 'Target':
				if(!isset($Text)) {
					if($MyRow->TargetNo) {
						$Text=array(ltrim($MyRow->TargetNo, '0'));
						$toCat=(intval($MyRow->ToCategory) & 12);
						if(!empty($MyRow->RealTarget) and !empty($toCat) and $MyRow->RealTarget>$MyRow->Ends) {
							$Text[0]=CheckBisTargets($MyRow->RealTarget, $MyRow->Ends) . substr($Text[0], -1);
						}
					} else {
						$Text=array('');
					}
				}
			case 'SessionTarget':
				if(!isset($Text)) {
					if($MyRow->TargetNo) {
						$Text=array($MyRow->TargetNo);
						if(!empty($MyRow->RealTarget) and ($MyRow->ToCategory & 12) and $MyRow->RealTarget>$MyRow->Ends) {
							$Text[0]=CheckBisTargets($MyRow->RealTarget, $MyRow->Ends) . substr($Text[0], -1);
						}
						$Text[0] = $MyRow->Session . "-" . $Text[0];
					} else {
						$Text=array('');
					}
				}
			case 'Access':
				if(!isset($Text)) {
					$txt='';
					for($i=0; $i<8; $i++) {
						if($MyRow->{'AcArea'.$i}) {
                            $txt .= $i;
                            if ($i < 2 and $MyRow->AcAreaStar) $txt .= '*';
                            $txt .= ' ';
						}
					}
					$Text=array(trim($txt));
				}

				if($Element->Options) {
	                if(strpos($Element->Options['Size']??'','-')!==false) {
	                    list($fSize,$fSpace) = explode('-',$Element->Options['Size']);
	                    $Element->Options['Size'] = $fSize;
	                    $pdf->setFontSpacing($fSpace);
	                } else {
	                    $pdf->setFontSpacing(0);
	                }
                    $pdf->SetFont($Element->Options['FontFamily'], $Element->Options['FontStyle'], $Element->Options['Size']);
                }
				$Fill=false;
				$WhiteText=false;
				$BlackText=false;
				if(!empty($Element->Options['BackCat'])) {
					$R=hexdec(substr($MyRow->AcColor??'000000', 0, 2));
					$G=hexdec(substr($MyRow->AcColor??'000000', 2, 2));
					$B=hexdec(substr($MyRow->AcColor??'000000', 4, 2));
					//if($Element->IceType=='ColoredArea') {
						$pdf->SetFillColor($R, $G, $B);
						$Fill=true;
					//}
					if(IsDarkBackground(array($R, $G, $B))) {
						$WhiteText=true;
					} else {
						$BlackText=true;
					}
				} elseif($Element->Options and $Element->Options['BackCol']) {
					$R=hexdec(substr($Element->Options['BackCol'], 1, 2));
					$G=hexdec(substr($Element->Options['BackCol'], 3, 2));
					$B=hexdec(substr($Element->Options['BackCol'], 5, 2));
					//if($Element->IceType=='ColoredArea') {
						$pdf->SetFillColor($R, $G, $B);
						$Fill=true;
					//}
					if(IsDarkBackground(array($R, $G, $B))) {
						$WhiteText=true;
					}
				}

				if(!empty($Element->Options['BackCat']) and $WhiteText) {
					$pdf->setColor('text', 255, 255, 255);
				} elseif($Element->Options and $Element->Options['Col']) {
					$R=hexdec(substr($Element->Options['Col'], 1, 2));
					$G=hexdec(substr($Element->Options['Col'], 3, 2));
					$B=hexdec(substr($Element->Options['Col'], 5, 2));
					if($WhiteText) {
						$pdf->setColor('text', 255, 255, 255);
					} elseif(!IsDarkBackground(array($R, $G, $B)) and $BlackText) {
						$pdf->setColor('text', 0, 0, 0);
					} else {
						$pdf->setColor('text', $R, $G, $B);
					}
				} else {
					$pdf->setColor('text', 0, 0, 0);
				}
				if($Fill and implode('', $Text)) $pdf->SetCellPadding(max(0.5, min($Element->Options['W'], $Element->Options['H'])/10));

				if($Element->Options) {
					foreach($Text as $k => $txt) {
						$pdf->SetXY($ElX, $ElY + ($k*$ElH));
	                    $pdf->Cell($Element->Options['W'], $ElH, $txt, 0, true,
								$Just[$Element->Options['Just']], $Fill);
					}
				}
				if($Fill) $pdf->SetCellPadding(0);
				break;
			case 'AthBarCode':
				$Fill=false;
				$ReverseText=false;
				if(!empty($Element->Options['BackCat'])) {
					$R=hexdec(substr($MyRow->AcColor, 0, 2));
					$G=hexdec(substr($MyRow->AcColor, 2, 2));
					$B=hexdec(substr($MyRow->AcColor, 4, 2));
					$pdf->SetFillColor($R, $G, $B);
					$Fill=true;
					if(IsDarkBackground(array($R, $G, $B))) $ReverseText=true;
				} elseif($Element->Options['BackCol']) {
					$R=hexdec(substr($Element->Options['BackCol'], 1, 2));
					$G=hexdec(substr($Element->Options['BackCol'], 3, 2));
					$B=hexdec(substr($Element->Options['BackCol'], 5, 2));
					$pdf->SetFillColor($R, $G, $B);
					$Fill=true;
					if(IsDarkBackground(array($R, $G, $B))) $ReverseText=true;
				}
				if(!empty($Element->Options['BackCat']) and $ReverseText) {
					$pdf->setColor('text', 255, 255, 255);
				} elseif($Element->Options['Col']) {
					$R=hexdec(substr($Element->Options['Col'], 1, 2));
					$G=hexdec(substr($Element->Options['Col'], 3, 2));
					$B=hexdec(substr($Element->Options['Col'], 5, 2));
					if(IsDarkBackground(array($R, $G, $B)) and $ReverseText) {
						$pdf->setColor('text', 255, 255, 255);
					} else {
						$pdf->setColor('text', $R, $G, $B);
					}
				} else {
					$pdf->setColor('text', 0, 0, 0);
				}
				$txt=$MyRow->Bib.'-'.$MyRow->DivCode.'-'.$MyRow->ClassCode;
				if($Element->IceContent) {
					$replacements=array(
						'{ENCODE}'=>$MyRow->Bib,
						'{COUNTRY}'=>$MyRow->NationCode,
						'{DIVISION}'=>$MyRow->DivCode,
						'{CLASS}'=>$MyRow->ClassCode,
						'{TOURNAMENT}'=>$MyRow->ToCode,
					);
					$txt=str_replace(array_keys($replacements), array_values($replacements), $Element->IceContent);
				}

				$pdf->SetXY($ElX, $ElY);
				$pdf->SetFont('barcode','',$Element->Options['H']*2.83);
				if($txt[0]=='_') $txt='UU'.substr($txt, 1);
				$pdf->Cell($Element->Options['W'], $Element->Options['H'], mb_convert_encoding('*' . $txt, "UTF-8","cp1252") . "*", 0, 0, 'C', $Fill);
				break;
			case 'AthQrCode':
				$style = array(
					'border' => 1,
					'vpadding' => 2,
					'hpadding' => 2,
					'fgcolor' => array(0,0,0),
					'bgcolor' => array(255,255,255), //array(255,255,255)
					'module_width' => 1, // width of a single module in points
					'module_height' => 1 // height of a single module in points
					);
				$Fill=false;
				$ReverseText=false;
				if(!empty($Element->Options['BackCat'])) {
					$R=hexdec(substr($MyRow->AcColor, 0, 2));
					$G=hexdec(substr($MyRow->AcColor, 2, 2));
					$B=hexdec(substr($MyRow->AcColor, 4, 2));
					$style['bgcolor']=array($R, $G, $B);
					$Fill=true;
					if(IsDarkBackground(array($R, $G, $B))) $ReverseText=true;
				} elseif($Element->Options['BackCol']) {
					$R=hexdec(substr($Element->Options['BackCol'], 1, 2));
					$G=hexdec(substr($Element->Options['BackCol'], 3, 2));
					$B=hexdec(substr($Element->Options['BackCol'], 5, 2));
					$style['bgcolor']=array($R, $G, $B);
					$Fill=true;
					if(IsDarkBackground(array($R, $G, $B))) $ReverseText=true;
				}

				if(!empty($Element->Options['BackCat']) and $ReverseText) {
					$style['fgcolor']=array(255, 255, 255);
					$pdf->setColor('text', 255, 255, 255);
				} elseif($Element->Options['Col']) {
					$R=hexdec(substr($Element->Options['Col'], 1, 2));
					$G=hexdec(substr($Element->Options['Col'], 3, 2));
					$B=hexdec(substr($Element->Options['Col'], 5, 2));
					if(IsDarkBackground(array($R, $G, $B)) and $ReverseText) {
						$style['fgcolor']=array(255, 255, 255);
						$pdf->setColor('text', 255, 255, 255);
					} else {
						$style['fgcolor']=array($R, $G, $B);
						$pdf->setColor('text', $R, $G, $B);
					}
				} else {
					$style['fgcolor']=array(0, 0, 0);
					$pdf->setColor('text', 0, 0, 0);
				}
				$txt=$MyRow->Bib.'-'.$MyRow->DivCode.'-'.$MyRow->ClassCode;
				if($Element->IceContent) {
					$replacements=array(
						'{ENCODE}'=>$MyRow->Bib,
						'{COUNTRY}'=>$MyRow->NationCode,
						'{DIVISION}'=>$MyRow->DivCode,
						'{CLASS}'=>$MyRow->ClassCode,
						'{TOURNAMENT}'=>$MyRow->ToCode,
						);
					$txt=str_replace(array_keys($replacements), array_values($replacements), $Element->IceContent);
				}
				$pdf->write2DBarcode($txt, 'QRCODE,L', $ElX, $ElY, $Element->Options['W'], $Element->Options['H'], $style, 'N');
				break;
			case 'HLine':
				if($Element->Options['Col']) {
					$R=hexdec(substr($Element->Options['Col'], 1, 2));
					$G=hexdec(substr($Element->Options['Col'], 3, 2));
					$B=hexdec(substr($Element->Options['Col'], 5, 2));
					$pdf->Line($ElX, $ElY, $ElX+$Element->Options['W'], $ElY, array('width' => $Element->Options['H'], 'color'=>array($R, $G, $B)));
					$pdf->SetLineWidth(0.01);
				}
				break;
			case 'TgtSequence':
                $tmpPhases = getStandardPhases();
                foreach ($tmpPhases as $kPh=>$vPh) {
                    if($vPh>$Element->Options['FromPhase'] OR $vPh<$Element->Options['ToPhase']) {
                        unset($tmpPhases[$kPh]);
                    }
                }
                $BigNameLineWidth=0.5;
                $R=hexdec(substr($Element->Options['Col'], 1, 2));
                $G=hexdec(substr($Element->Options['Col'], 3, 2));
                $B=hexdec(substr($Element->Options['Col'], 5, 2));
                $pdf->SetTextColor($R,$G,$B);
                $BigNameColors=array(
                    0 => array(128),
                    1 => array(128),
                    2 => array(251, 191, 21),
                    4 => array(239, 46, 49),
                    8 => array(64, 193, 230),
                    16 => array(33, 81, 168),
                    32 => array(79, 190, 55),
                    64 => array(237, 43, 159),
                );
                if($Element->IceContent=='BlackWhite') {
                    $R=hexdec(substr($Element->Options['Col'], 1, 2));
                    $G=hexdec(substr($Element->Options['Col'], 3, 2));
                    $B=hexdec(substr($Element->Options['Col'], 5, 2));
                    $pdf->SetTextColor($R,$G,$B);
                    if(!empty($Element->Options['BackCol'])) {
                        $R=hexdec(substr($Element->Options['BackCol'], 1, 2));
                        $G=hexdec(substr($Element->Options['BackCol'], 3, 2));
                        $B=hexdec(substr($Element->Options['BackCol'], 5, 2));
                    }
                    $BigNameLineWidth=0.05;
                    $BigNameColors=array(
                        0 => array($R,$G,$B),
                        1 => array($R,$G,$B),
                        2 => array($R,$G,$B),
                        4 => array($R,$G,$B),
                        8 => array($R,$G,$B),
                        16 => array($R,$G,$B),
                        32 => array($R,$G,$B),
                        64 => array($R,$G,$B)
                    );
                }
                $OldLineWidth=$pdf->GetLineWidth();
                $OldColor=$pdf->getDrawColor();
                $OldCellPadding=$pdf->getCellPaddings();
                $OldCellHeightRatio=$pdf->getCellHeightRatio();
                $pdf->SetFont($Element->Options['FontFamily'], $Element->Options['FontStyle'],$Element->Options['Size']);
                $pdf->SetLineWidth($BigNameLineWidth);
                $pdf->SetCellPaddings(1,0,1,0);
                //$pdf->setCellHeightRatio(0);
				// PArte di riconoscimento EVENTO e Paglione
				$TgtQuery = 'SELECT
						EvCode, EvEventName, EvFinalFirstPhase, GrPhase,
						GrMatchNo, EnId, Concat(EnFirstName, " ", LEFT(EnName,1), ".") as Athlete,
						CoCode, CoName, IndRank as `Rank`,
						NULLIF(s64.FSLetter,\'\') s64, NULLIF(s32.FSLetter,\'\') s32, NULLIF(s16.FSLetter,\'\') s16, NULLIF(s8.FSLetter,\'\') s8, NULLIF(s4.FSLetter,\'\') s4, NULLIF(s2.FSLetter,\'\') s2, NULLIF(sb.FSLetter,\'\') sBr, NULLIF(sg.FSLetter,\'\') sGo
					FROM Events
					INNER JOIN Phases on PhId=EvFinalFirstPhase and (PhIndTeam & pow(2,EvTeamEvent))>0
					INNER JOIN Finals ON EvCode=FinEvent AND EvTournament=FinTournament
					INNER JOIN Grids ON FinMatchNo=GrMatchNo AND GrPhase=greatest(PhId, PhLevel)
					INNER JOIN Individuals ON FinAthlete=IndId AND FinEvent=IndEvent AND FinTournament=IndTournament
					left JOIN Entries ON FinAthlete=EnId AND FinTournament=EnTournament
					LEFT JOIN Qualifications ON QuId=EnId
					LEFT JOIN Countries on EnCountry=CoId AND EnTournament=CoTournament
					LEFT JOIN FinSchedule s64 ON EvCode=s64.FSEvent AND EvTeamEvent=s64.FSTeamEvent AND EvTournament=s64.FSTournament AND IF(GrPhase=64, FinMatchNo,-256)=s64.FSMatchNo
					LEFT JOIN FinSchedule s32 ON EvCode=s32.FSEvent AND EvTeamEvent=s32.FSTeamEvent AND EvTournament=s32.FSTournament AND IF(GrPhase=32,FinMatchNo,FLOOR(s64.FSMatchNo/2))=s32.FSMatchNo
					LEFT JOIN FinSchedule s16 ON EvCode=s16.FSEvent AND EvTeamEvent=s16.FSTeamEvent AND EvTournament=s16.FSTournament AND IF(GrPhase=16,FinMatchNo,FLOOR(s32.FSMatchNo/2))=s16.FSMatchNo
					LEFT JOIN FinSchedule s8 ON EvCode=s8.FSEvent AND EvTeamEvent=s8.FSTeamEvent AND EvTournament=s8.FSTournament AND IF(GrPhase=8,FinMatchNo,FLOOR(s16.FSMatchNo/2))=s8.FSMatchNo
					LEFT JOIN FinSchedule s4 ON EvCode=s4.FSEvent AND EvTeamEvent=s4.FSTeamEvent AND EvTournament=s4.FSTournament AND IF(GrPhase=4,FinMatchNo,FLOOR(s8.FSMatchNo/2))=s4.FSMatchNo
					LEFT JOIN FinSchedule s2 ON EvCode=s2.FSEvent AND EvTeamEvent=s2.FSTeamEvent AND EvTournament=s2.FSTournament AND IF(GrPhase=2,FinMatchNo,FLOOR(s4.FSMatchNo/2))=s2.FSMatchNo
					LEFT JOIN FinSchedule sb ON EvCode=sb.FSEvent AND EvTeamEvent=sb.FSTeamEvent AND EvTournament=sb.FSTournament AND FLOOR(s2.FSMatchNo/2)=sb.FSMatchNo
					LEFT JOIN FinSchedule sg ON EvCode=sg.FSEvent AND EvTeamEvent=sg.FSTeamEvent AND EvTournament=sg.FSTournament AND FLOOR(s2.FSMatchNo/2)-2=sg.FSMatchNo
					WHERE EvTournament=' . StrSafe_DB($MyRow->EnTournament) . ' AND EvCode='.StrSafe_DB($MyRow->EvCode).' and EvTeamEvent=0 and EnId='.$MyRow->EnId.'
					ORDER BY EvCode, GrPhase DESC, FinMatchNo ASC';

				$TgtQ=safe_r_sql($TgtQuery);
				if($TgtR=safe_fetch($TgtQ)) {
                    foreach ($tmpPhases as $kPh=>$vPh) {
                        if($vPh>bitwisePhaseId($TgtR->EvFinalFirstPhase)) {
                            unset($tmpPhases[$kPh]);
                        }
                    }
                    if(count($tmpPhases)) {
                        $BoxWidth = floatval($Element->Options['W']) / (count($tmpPhases));
                        $BoxHeight = $Element->Options['H'];
                        if($Element->Options['LayoutOrientation']=="1") {
                            $BoxWidth = $Element->Options['W'];
                            $BoxHeight = floatval($Element->Options['H']) / (count($tmpPhases));
                        }
                        $GoldBox = get_text('0_Phase');
                        $BronzeBox = get_text('1_Phase');
                        $SemiBox = get_text('2_Phase');

                        $pdf->SetXY($ElX + $Element->Options['W'] - $BoxWidth, $ElY);
                        if (!empty($TgtR->sGo) and in_array('0', $tmpPhases)) {
                            $pdf->setColorArray('draw', $BigNameColors[0]);
                            $pdf->Cell($BoxWidth, $BoxHeight, $GoldBox . ": " . ltrim($TgtR->sGo, '0'), 1, 0, $Just[$Element->Options['Just']], 0, '', 1, true, 'T', 'M');
                            if($Element->Options['LayoutOrientation']=="0") {
                                $pdf->SetX($pdf->getX() - $BigNameLineWidth - $BoxWidth * 2);
                            } else {
                                $pdf->SetXY($pdf->getX() - $BoxWidth, $pdf->getY()+$BigNameLineWidth+$BoxHeight);
                            }
                        }
                        if (!empty($TgtR->sBr) and in_array('1', $tmpPhases)) {
                            $pdf->setColorArray('draw', $BigNameColors[1]);
                            $pdf->Cell($BoxWidth, $BoxHeight, $BronzeBox . ": " . ltrim($TgtR->sBr, '0'), 1, 0, $Just[$Element->Options['Just']], 0, '', 1, true, 'T', 'M');
                            if($Element->Options['LayoutOrientation']=="0") {
                                $pdf->SetX($pdf->getX() - $BigNameLineWidth - $BoxWidth * 2);
                            } else {
                                $pdf->SetXY($pdf->getX() - $BoxWidth, $pdf->getY()+$BigNameLineWidth+$BoxHeight);
                            }
                        }
                        for ($i = 2; $i <= valueFirstPhase($TgtR->EvFinalFirstPhase); $i = $i * 2) {
                            if (in_array($i, $tmpPhases)) {
                                $pdf->setColorArray('draw', $BigNameColors[$i]);
                                $pdf->Cell($BoxWidth, $BoxHeight, '1/' . namePhase($TgtR->EvFinalFirstPhase, $i) . ': ' . ltrim($TgtR->{'s' . $i}, '0'), 1, 0, $Just[$Element->Options['Just']], 0, '', 1, true, 'T', 'M');
                                if (empty($TgtR->{'s' . $i})) { //} OR is_null($TgtR->{'s' . $i})) {
                                    $pdf->line($pdf->getX(), $pdf->getY(), $pdf->getX() - $BoxWidth, $pdf->GetY() + $BoxHeight + $BigNameLineWidth * 2);
                                }
                                if($Element->Options['LayoutOrientation']=="0") {
                                    $pdf->SetX(max(0, $pdf->getX() - $BigNameLineWidth - $BoxWidth * 2));
                                } else {
                                    $pdf->SetXY($pdf->getX() - $BoxWidth, $pdf->getY()+$BigNameLineWidth+$BoxHeight);
                                }
                            }
                        }
                    }
				}
                $pdf->SetDrawColor($OldColor);
                $pdf->SetTextColor(0x00, 0x00, 0x00);
                $pdf->SetCellPaddings($OldCellPadding['L'], $OldCellPadding['T'],$OldCellPadding['R'],$OldCellPadding['B']);
                $pdf->setCellHeightRatio($OldCellHeightRatio);
                $pdf->SetLineWidth($OldLineWidth);

				break;
			default:
		}
	}

//Crop Marks

	if($cntPass) {
		if($StartY!=0) {
			$pdf->Line($StartX-5, $StartY, $StartX+10, $StartY, array('width'=>0.01, 'color'=>array(0)));
			$pdf->Line($tx=$pdf->getPageWidth()-10, $StartY, $tx+10, $StartY, array('width'=>0.01, 'color'=>array(0)));
		}
		if($StartX!=0) {
			$pdf->Line($StartX, $StartY-5, $StartX, $StartY+5, array('width'=>0.01, 'color'=>array(0)));
			$pdf->Line($StartX, $ty=$pdf->getPageHeight()-5, $StartX, $ty+5, array('width'=>0.01, 'color'=>array(0)));
		}
	}

	$cntPass++;
	if($cntPass >= count($Badges)) {
        $cntPass=0;
    }
}
// 	$pdf->deletePage(1);

safe_free_result($Rs);

if(isset($_REQUEST['ToString'])) {
    echo base64_encode($pdf->Output('ToString','S'));
} else if(empty($ReturnAsString)) {
	$pdf->Output();
}


function getSystemMemInfo() {
	if(!file_exists("/proc/meminfo")) {
		return;
	}
	$data = explode("\n", file_get_contents("/proc/meminfo"));
	$meminfo = array();
	foreach ($data as $line) {
		if($line) {
			list($key, $val) = explode(":", $line);
			$meminfo[$key] = trim($val);
		} else {
			//debug_svela($line);
		}
	}
	return $meminfo;
}

