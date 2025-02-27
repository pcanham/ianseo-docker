<?php

require_once(dirname(dirname(__FILE__)).'/config.php');
checkACL(AclRoot, AclReadWrite);

require_once('Common/Lib/Fun_Modules.php');
require_once('Common/Lib/CommonLib.php');
$Options=GetParameter('AccessApp', '', array(), true);

if(!empty($_REQUEST['addTour']) and $ToId=intval($_REQUEST['addTour'])) {
	if(!isset($Options[$ToId])) {
		$Options[$ToId]=array();
		SetParameter('AccessApp', $Options, true);
	}

	CD_redirect(basename(__FILE__));
}

if(!empty($_REQUEST['delete']) and $ToId=intval($_REQUEST['delete'])) {
	unset($Options[$ToId]);
	SetParameter('AccessApp', $Options, true);

	CD_redirect(basename(__FILE__));
}

$PAGE_TITLE=get_text('MenuLM_GateControl');
$IncludeJquery = true;
$JS_SCRIPT=array(
	'<script type="text/javascript" src="./GateControl.js"></script>',
);

require_once('Common/Templates/head.php');


echo '<table class="Tabella">';
echo '<tr><th class="Title" colspan="3">' . get_text('MenuLM_GateControl') . '</th></tr>';
// ask to insert a new competition
echo '<tr>';
echo '<th>'.get_text('AddTournament', 'Tournament').'</th>';
echo '<td colspan="2"><select onchange="location.href=\'?addTour=\'+this.value">';
echo '<option value="0">---</option>';
$q=safe_r_sql("select ToId, ToCode, ToName, ToWhere, ToWhenFrom, ToWhenTo from Tournament 
	".($Options ? "where ToId not in (".implode(',', array_keys($Options)).")" : '')." 
	order by ToWhenTo desc, ToWhenFrom desc");
while($r=safe_fetch($q)) {
	echo '<option value="'.$r->ToId.'">'.$r->ToCode.' - '.$r->ToName.' ('.$r->ToWhenFrom.')</option>';
}
echo '</select></td>';
echo '</tr>';

foreach($Options as $ToId => $Sessions) {
    $toCode=getCodeFromId($ToId);
	echo '<tr>';
	echo '<th>
		<div>'.$toCode.'</div>
		<div><input type="checkbox" value="'.$ToId.'" checked="checked" onclick="loadCombo(this)">'.get_text('ScheduleToday', 'Tournament').'</div>
		<div><input type="button" value="'.get_text('ClearField', 'Tournament').'" onclick="clearField('.$ToId.')" </div>
		</th>';

	// get all the sessions defined by Qualification, Elimination and Matches
	echo '<td id="Combo-'.$ToId.'">';
	if($tmp=getApiScheduledSessions(['TourId' => $ToId, 'OnlyToday' => true])) {
		echo '<table><tr><th>Event</th><th>Byes In</th><th>Byes Out</th></tr>';
			foreach($tmp as $item) {
				echo '<tr class="rowHover"><td>'.$item->Description.'</td>';
				echo '<td align="center"><input type="checkbox" name="'.$item->keyValue.'-'.$ToId.'" value="'.$item->keyValue.'" tour="'.$ToId.'" onclick="setSession(this)"'.(in_array($item->keyValue, $Sessions) ? ' checked="checked"' : '').' ></td>';
				echo '<td align="center"><input type="checkbox" name="'.$item->keyValue.'-'.$ToId.'" value="'.strtolower($item->keyValue).'" tour="'.$ToId.'" onclick="setSession(this)"'.(in_array(strtolower($item->keyValue), $Sessions) ? ' checked="checked"' : '').' ></td>';
				echo '</tr>';
			}
		echo '</table>';
	}
	echo '</td>';

	echo '<td><img src="'.$CFG->ROOT_DIR.'Common/Images/drop.png" onclick="location.href=\'?delete='.$ToId.'\'"></td>';
	echo '</tr>';
}
echo '</table>';

require_once('Common/Templates/tail.php');
