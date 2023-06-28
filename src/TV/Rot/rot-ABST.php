<?php
require_once('Common/Lib/Obj_RankFactory.php');
require_once('Common/Fun_FormatText.inc.php');

function rotAbst($TVsettings, $RULE) {
	global $CFG, $IsCode, $TourId, $SubBlock;
	$CSS=unserialize($TVsettings->TVPSettings);
	getPageDefaults($CSS);
	$Return=array(
		'CSS' => $CSS,
		'html' => '',
		'Block' => 'QualRow',
		'BlockCss' => 'height:auto; width:100%; padding-right:0.5rem; overflow:hidden; font-size:2em; display:flex; flex-direction:row; justify-content:space-between; align-items:center; box-sizing:border-box;',
		'NextSubBlock' => 1,
		'SubBlocks' => 1);
	$ret=array();

	$TourType=getTournamentType($TourId);

	$TVsettings->EventFilter=MakeEventFilter($TVsettings->TVPEventTeam);

	$options=array('tournament' => $RULE->TVRTournament);
	$options['dist'] = 0;
	$options['records'] = 1;

	if(isset($TVsettings->TVPEventTeam) && !empty($TVsettings->TVPEventTeam))
		$options['events'] = explode('|',$TVsettings->TVPEventTeam);
	if(isset($TVsettings->TVPNumRows) && $TVsettings->TVPNumRows>0)
		$options['cutRank'] = $TVsettings->TVPNumRows;
	if(isset($TVsettings->TVPSession) && $TVsettings->TVPSession>0)
		$options['session'] = $TVsettings->TVPSession;

	$Columns=(isset($TVsettings->TVPColumns) && !empty($TVsettings->TVPColumns) ? explode('|',$TVsettings->TVPColumns) : array());
	$ViewAthletes=(in_array('ATHL', $Columns) or in_array('ALL', $Columns));
	$ViewFlag=(in_array('FLAG', $Columns) or in_array('ALL', $Columns));
	$ViewCode=(in_array('CODE', $Columns) or in_array('ALL', $Columns));
	$ViewDists=((in_array('DIST', $Columns) or in_array('ALL', $Columns)) and $TVsettings->TVPViewPartials);
	$View10s=(in_array('10', $Columns) or in_array('ALL', $Columns));
	$ViewX9s=(in_array('X9', $Columns) or in_array('ALL', $Columns));
	$ViewArrows=(in_array('ARROWS', $Columns) or in_array('ALL', $Columns));
	$Title2Rows=(in_array('TIT2ROWS', $Columns) ? '<br/>' : ': ');
	$Title2Arrows=(in_array('TIT2ROWS', $Columns) ? '<br/>' : ' - ');

	$comparedTo=preg_grep('/^COMP:/', $Columns);
	if(!empty($comparedTo)) {
		list(,$comparedTo) = explode(":",reset($comparedTo));
		$options['comparedTo'] = $comparedTo;
	}

	$Fixed=preg_grep('/^FIXED:/', $Columns);
	$FixedDone=false;
	if(!empty($Fixed)) {
		list(,$Fixed) = explode(":",reset($Fixed));
	}

	$rank=Obj_RankFactory::create('AbsTeam',$options);
	$rank->read();
	$rankData=$rank->getData();

	$Return['SubBlocks']=count($rankData['sections']);

	if($SubBlock>count($rankData['sections'])) $SubBlock=1;
	if(count($rankData['sections'])==0) return $Return;

	$Return['NextSubBlock']=($SubBlock+1);

	foreach($rankData['sections'] as $IdEvent => $data) {
		$SubBlock--;
		if(!$SubBlock) {
			break;
		}
	}

	$FieldForGold=($data['meta']['running'] ? 'completeScore' : 'gold');
	$FieldForXNine=($data['meta']['running'] ? 'hits' : 'xnine');

	$RecTot=array();
	$RecTitle='';
	$RecCut=999999;
	$retToAdd='';
	if($data['meta']['arrowsShot']) {
		// Records handling
		$RecTot=array();
		$RecordCut=array();
		$RecTitle='';
		$RecCut=0;
		$RecXNine=0;
		$RecCols=array();

		// Records handling
		$ExtraCSS='';
		$NumRecords=0;
		$Final=(max($data['meta']['arrowsShot'])==$data['meta']['maxArrows']);
		foreach($data['records'] as $r) {
			$RecTot[$r->RtRecCode]['tot']=$r->RtRecMaxScore-$r->RtRecTotal;
			$RecTot[$r->RtRecCode]['gap']=$r->TrGaps;
			$RecTot[$r->RtRecCode]['area']=$r->ReArBitLevel;
			$RecTot[$r->RtRecCode]['claim']=$r->ReArMaCode;
			if($r->TrGaps and !$Final) {
				$RecTitle.='<div class="piccolo" style="color:#'.$r->TrColor.'">'.($Final ? $r->TrHeader : get_text('RecordAverage', 'Records', $r->TrHeader)).'</div>';
			}
			$RecCut=max($RecCut, $RecTot[$r->RtRecCode]['tot']);
			$rec=round($r->RtRecTotal*max($data['meta']['arrowsShot'])/$data['meta']['maxArrows'],1);
			if($r->TrBars) {
				$NumRecords++;
				$ExtraCSS.=".Rec_{$r->RtRecCode} {background-color:#{$r->TrColor}; color:white;".($r->TrFontFile ? 'font-family:'.$r->RtRecCode.';' : '')."}";
				$tmp='<div class="QualRow Rec_'. $r->RtRecCode.'">
						<div class="Record">'.get_text('RecordAverage', 'Records', $r->TrHeader).'</div>
					<div class="Score">' . ($data['meta']['running'] ? number_format($rec/$data['meta']['maxArrows'], 3, '.', '') : number_format($rec, $Final ? 0 : 1, '.', '')) . '</div>
					'.($View10s ?  '<div class="Gold">&nbsp;</div>' : '').'
					'.($ViewX9s ? '<div class="XNine">&nbsp;</div>' : '').'
					</div>';
				$RecordCut["$rec"][]=$tmp;
			}
		}

		if($ExtraCSS) $Return['BlockCss'].="} $ExtraCSS {";
	}

	// TITLE
	$tmp = '';

// 	$ret[]='<div class="Title">'.$rankData['meta']['title'].$Title2Rows.$data['meta']['descr'].$Title2Rows.$RecTitle .'</div>';
// DELETED FOR OLYMPICS
// 	$ret[]='<div class="Title">'.$data['meta']['descr'].$Title2Rows.$RecTitle .'</div>';
	$ret[]='<div class="Title">
				<div class="TitleImg" style="float:left;"><img src="'.$CFG->ROOT_DIR.'TV/Photos/'.$IsCode.'-ToLeft.jpg"></div>
				<div class="TitleImg" style="float:right;"><img src="'.$CFG->ROOT_DIR.'TV/Photos/'.$IsCode.'-ToRight.jpg"></div>
				'.$data['meta']['descr'] .'
			</div>';

	// Header header;
	$tmp ='<div class="QualRow Headers">';


	// al 4° devo interrompere la tabella e aprire il resto in un div separato
	$tmp.='<div class="Rank Headers">' . $data['meta']['fields']['rank'] . '</div>';
	if(!empty($comparedTo)) {
		$tmp.='<div class="RankOld">&nbsp;</div>';
	}

	$tmp.=str_repeat('<div class="RecBar"></div>', $NumRecords);

	if($ViewCode) {
		$tmp.='<div class="CountryCode Rotate">&nbsp;</div>';
	}
	if($ViewFlag) {
		$tmp.='<div class="FlagDiv">&nbsp;</div>';
	}

	$tmp.='<div class="CountryDescr Headers">' . $data['meta']['fields']['countryName'] . '</div>';

	if($ViewAthletes) {
		$tmp.='<div class="Athlete Headers">'.$data['meta']['fields']['athletes']['name'].'</div>';
	}

	if($ViewArrows) $tmp.='<div class="Arrows Headers">' . $data['meta']['fields']['hits'] . '</div>';

	$tmp.='<div class="Score Headers">' . $data['meta']['fields']['score'] . '</div>';
	if($data['meta']['running']) $tmp.='<div class="Arrows Headers">' . $data['meta']['fields']['completeScore'] . '</div>';
	if($View10s) $tmp.='<div class="Gold Headers">' . $data['meta']['fields'][$FieldForGold] . '</div>';
	if($ViewX9s) $tmp.='<div class="XNine Headers">' . $data['meta']['fields'][$FieldForXNine] . '</div>';
	$tmp.='</div>';
	$ret[]=$tmp;

	$ret[]=$retToAdd;

	if(!$Fixed) {
		$ret[]='<div id="content" data-direction="up">';
		$FixedDone=true;
	}

	// Inserisci adesso le singole righe
	foreach($data['items'] as $key => $archer) {
		if($Fixed and $archer['rank']>$Fixed and !$FixedDone) {
			$ret[]='<div id="content" data-direction="up">';
			$FixedDone=true;
		}

		foreach ($RecordCut as $Record => $FormattedRows) {
			if($archer['completeScore'] <= $Record) {
				$ret[]=implode('', $FormattedRows);
				unset($RecordCut[$Record]);
			}
		}

		$Class=($key%2 == 0 ? 'e': 'o');
		$tmp ='<div class="QualRow Font1'.$Class.' Back1'.$Class.'">';


		$tmp.='<div class="Rank">' . $archer['rank'] . '</div>';
		if(!empty($comparedTo)) {
			$cl="RankNone";
			if($archer['oldRank']) {
				if($archer['rank']==$archer['oldRank']) {
					$cl='RankMinus';
				} elseif($archer['rank']<$archer['oldRank']) {
					$cl='RankUp';
				} else {
					$cl='RankDown';
				}
			}
			$tmp.='<div class="RankOld '.$cl.'">' . ($archer['oldRank']&& $archer['oldRank']!=$archer['rank'] ? $archer['oldRank']:'&nbsp;'). '</div>';
		}

		foreach ($RecTot as $RecCode => $Record) {
			if(!$Final and $Record['gap'] and $archer['recordGap'] < $Record['tot'] and
				(!$Record['claim']
					or $Record['claim']==$archer['contAssoc']
					or $Record['claim']==$archer['memberAssoc'])) {
				$tmp.='<div class="RecBar Rec_'. $RecCode.'"></div>';
			} else {
				$tmp.='<div class="RecBar"></div>';
			}
		}


		if($ViewCode) {
			$tmp.='<div class="CountryCode Rotate Rev1'.$Class.'">' . $archer['countryCode'] . '</div>';
		}
		if($ViewFlag) {
			$tmp.='<div class="FlagDiv">'.get_flag_ianseo($archer['countryCode'], '', '', $IsCode).'</div>';
		}


		$tmp.='<div class="CountryDescr">' . $archer['countryName'] . '</div>';
		if($ViewAthletes) {
			$tmp.='<div class="Athlete">';
			foreach($archer['athletes'] as $athl) {
				$tmp.= '<div class="AthleteNames">'.$athl['athlete'].'</div>';
			}
			$tmp.='</div>';
		}

		if($ViewArrows) $tmp.='<div class="Arrows">' . $archer['hits'] . '</div>';
		$tmp.='<div class="Score">' . $archer['score'] . '</div>';
		if($data['meta']['running']) $tmp.='<div class="Arrows">' . $archer['completeScore'] . '</div>';
		if($View10s) $tmp.='<div class="Gold">' . $archer[$FieldForGold] . '</div>';
		if($ViewX9s) $tmp.='<div class="XNine">' . $archer[$FieldForXNine] . '</div>';
		$tmp.='</div>';
		$ret[]=$tmp;
	}
	if($FixedDone) $ret[]='</div>';

	$Return['html']=implode('', $ret);

	return $Return;
}

function rotAbstSettings($Settings) {
	global $CFG;
	$ret='<br/>';
	$ret.= '<table class="Tabella Css3">';
	$ret.= '<tr><th colspan="3">'.get_text('TVCss3SpecificSettings','Tournament').'</th></tr>';

	// defaults for fonts, colors, size
	$RMain=array();
	if(!empty($Settings)) {
		$RMain=unserialize($Settings);
	}

	$PageDefaults=getPageDefaults($RMain);

	// 	if(!isset($RMain[''])) $RMain['']='';
	// if(!isset($RMain[''])) $RMain['']='';

	foreach($PageDefaults as $key => $Value) {
		$ret.= '<tr>
			<th nowrap="nowrap" class="Right">'.get_text('TVCss3'.$key,'Tournament').' <input type="button" value="reset" onclick="document.getElementById(\'P-Main['.$key.']\').value=\''.$Value.'\'"></th>
			<td width="100%"><input type="text" name="P-Main['.$key.']" id="P-Main['.$key.']" value="'.$RMain[$key].'"></td>
			</tr>';
	}
	return $ret;
}

function getPageDefaults(&$RMain) {
	global $CFG;
	$ret=array(
		'Title' => '',
		'RankOld' => 'background-repeat:no-repeat; background-size: contain; background-position:center;color:#FFFFFF; font-weight:bold; font-size:60%;',
		'RankNone' => '',
		'RankUp' => 'background: url(\'' . $CFG->ROOT_DIR . 'Common/Images/Up.png\');',
		'RankDown' => 'background: url(\'' . $CFG->ROOT_DIR . 'Common/Images/Down.png\');',
		'RankMinus' => 'background: url(\'' . $CFG->ROOT_DIR . 'Common/Images/Minus.png\');',
		'Rank' => 'flex: 0 0 4vw; text-align:right;',
		'CountryCode' => 'flex: 0 0 3.5vw; font-size:0.8vw; margin-left:-3.75ch',
		'FlagDiv' => 'flex: 0 0 4.35vw;',
		'Flag' => 'height:2.8vw; border:0.05vw solid #888;box-sizing:border-box;',
		'Target' => 'flex: 0 0 6vw; text-align:right;margin-right:0.5em;',
		'Athlete' => 'flex: 1 1 20vw;font-size:0.5em;white-space:nowrap;overflow:hidden;',
		'CountryDescr' => 'flex: 1 1 25vw;white-space:nowrap;overflow:hidden;',
		'Arrows' => 'flex: 0 0 5vw; text-align:right; font-size:1em;margin-right:0.5rem;',
		'DistScore' => 'flex: 0 0 5vw; text-align:right; font-size:0.8em;',
		'DistPos' => 'flex: 0 0 3vw; text-align:left; font-size:0.7em;',
		'Score' => 'flex: 0 0 6vw; text-align:right; font-size:1.25em;margin-right:0.5rem;',
		'Gold' => 'flex: 0 0 3vw; text-align:right; font-size:1em;',
		'XNine' => 'flex: 0 0 3vw; text-align:right; font-size:1em;',
	);
	foreach($ret as $k=>$v) {
		if(!isset($RMain[$k])) $RMain[$k]=$v;
	}
	return $ret;
}

