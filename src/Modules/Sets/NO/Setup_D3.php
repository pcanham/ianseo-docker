<?php
/*

COMMON SETUP 3D

*/

require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(dirname(__FILE__)).'/lib.php');

// default Divisions
CreateStandardDivisions($TourId, $TourType, $SubRule);

// default Classes
CreateStandardClasses($TourId, $SubRule, $TourType); //

// default Subclasses
CreateStandardSubClasses($TourId);

if($SubRule==9) {
	// Champs
	CreateStandard3DEvents($TourId, $SubRule, $TourType);
	InsertStandard3DEvents($TourId, $SubRule);
}

// default Distances & Default Target
if($tourDetNumDist==1) {
	if($SubRule==9) {
		CreateDistanceNew($TourId, $TourType, 'RD%', array(array('Blå',0)));
		CreateDistanceNew($TourId, $TourType, 'RH%', array(array('Blå',0)));
		CreateDistanceNew($TourId, $TourType, 'RR%', array(array('Gul',0)));
		CreateDistanceNew($TourId, $TourType, 'CD%', array(array('Rød',0)));
		CreateDistanceNew($TourId, $TourType, 'CH%', array(array('Rød',0)));
		CreateDistanceNew($TourId, $TourType, 'CR%', array(array('Blå',0)));
		CreateDistanceNew($TourId, $TourType, 'BUBU', array(array('Rød',0)));
		CreateDistanceNew($TourId, $TourType, 'BU18', array(array('Blå',0)));
		CreateDistanceNew($TourId, $TourType, 'BR', array(array('Gul',0)));
		CreateDistanceNew($TourId, $TourType, 'B_i', array(array('Blå',0)));
		CreateDistanceNew($TourId, $TourType, 'LBU18', array(array('Blå',0)));
		CreateDistanceNew($TourId, $TourType, 'LBR', array(array('Gul',0)));
		CreateDistanceNew($TourId, $TourType, 'LB_i', array(array('Blå',0)));
		CreateDistanceNew($TourId, $TourType, 'TU18', array(array('Blå',0)));
		CreateDistanceNew($TourId, $TourType, 'TR', array(array('Gul',0)));
		CreateDistanceNew($TourId, $TourType, 'T_i', array(array('Blå',0)));
	} else {
		CreateDistanceNew($TourId, $TourType, '%', array(array('Bane',0)));
	}
	CreateTargetFace($TourId, 1, '~Default', '%', '1', 8, 0);
} else {
	CreateDistanceNew($TourId, $TourType, '%', array(array('Bane 1',0), array('Bane 2',0)));
	CreateTargetFace($TourId, 1, '~Default', '%', '1', 8, 0, 8, 0);
}

// create a first distance prototype
CreateDistanceInformation($TourId, $DistanceInfoArray, $tourDetNumEnds, 6);

// Update Tour details
$tourDetails=array(
	'ToCollation' => $tourCollation,
	'ToTypeName' => $tourDetTypeName,
	'ToNumDist' => $tourDetNumDist,
	'ToNumEnds' => $tourDetNumEnds,
	'ToMaxDistScore' => $tourDetMaxDistScore,
	'ToMaxFinIndScore' => $tourDetMaxFinIndScore,
	'ToMaxFinTeamScore' => $tourDetMaxFinTeamScore,
	'ToCategory' => $tourDetCategory,
	'ToElabTeam' => $tourDetElabTeam,
	'ToElimination' => $tourDetElimination,
	'ToGolds' => $tourDetGolds,
	'ToXNine' => $tourDetXNine,
	'ToGoldsChars' => $tourDetGoldsChars,
	'ToXNineChars' => $tourDetXNineChars,
	'ToDouble' => $tourDetDouble,
	'ToIocCode'	=> $tourDetIocCode,
	);
UpdateTourDetails($TourId, $tourDetails);

?>
