<?php

if($version<'2013-01-03 15:27:00') {
	safe_w_sql("update TVContents set
			TVCContent=".strsafe_DB(file_get_contents(dirname(__FILE__).'/Images/ianseo.png')).",
			TVCMimeType='image/png'
			where TVCId=1 and TVCTournament=-1");

	db_save_version('2013-01-03 15:27:00');
}

if($version<'2013-01-20 17:45:00') {
	safe_w_sql("drop table if exists Vegas ");
	safe_w_sql("CREATE TABLE IF NOT EXISTS Vegas (
		VeId int(10) unsigned NOT NULL,
		VeArrowstring varchar(90) NOT NULL,
		VeScore smallint(6) NOT NULL,
		VeX smallint(6) NOT NULL,
		VeRank smallint(6) NOT NULL,
		VeSubClass varchar(2) NOT NULL,
		VeTimestamp datetime DEFAULT NULL,
		PRIMARY KEY (VeId),
		index VeScore (VeScore,VeX)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8");
	db_save_version('2013-01-20 17:45:00');
}

if($version<'2013-01-21 18:10:00') {
	$q="ALTER TABLE `Tournament` ADD `ToTimeZone` varchar(6) NOT NULL DEFAULT '' AFTER `ToIocCode` ";
	$r=safe_w_sql($q,false,array(1060));

	db_save_version('2013-01-21 18:10:00');
}

if($version<'2013-01-21 21:10:00') {
	$q="ALTER TABLE `Tournament` change `ToTimeZone` `ToTimeZone` varchar(50) NOT NULL DEFAULT '' ";
	$r=safe_w_sql($q,false,array(1060));

	db_save_version('2013-01-21 21:10:00');
}

if($version<'2013-01-22 13:10:00') {
	$q="ALTER TABLE `Tournament` change `ToTimeZone` `ToTimeZone` varchar(50) NOT NULL DEFAULT '' ";
	$r=safe_w_sql($q,false,array(1060));

	db_save_version('2013-01-22 13:10:00');
}

if($version<'2013-01-25 15:10:00') {
	safe_w_sql("drop table if exists VegasAwards ");
	$q="CREATE TABLE IF NOT EXISTS VegasAwards (
		VaTournament int(11) NOT NULL,
		VaDivision varchar(2) NOT NULL,
		VaClass varchar(2) NOT NULL,
		VaSubClass varchar(2) NOT NULL,
		VaRank int(10) NOT NULL,
		VaAward float(15,2) not null DEFAULT 0,
		VaToDelete TINYINT not null default 0,
		PRIMARY KEY (VaTournament, VaDivision, VaClass, VaSubClass, VaRank)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8";
	$r=safe_w_sql($q,false,array(1060));

	// not every country has "strong" money!
	$q="alter TABLE AccPrice change APPrice APPrice float(15,2) not null DEFAULT 0";
	$r=safe_w_sql($q,false,array(1060));
	db_save_version('2013-01-25 15:10:00');
}

if($version<'2013-02-01 12:37:00') {
	safe_w_sql("update InvolvedType set ItOc=ItOc+1 where ItOc>0");
	safe_w_sql("update InvolvedType set ItOc=ItOc+1 where ItOc>4");
	$q="insert into InvolvedType value (13, 'TecDelegate', 0, 0, 0, 1), (14, 'SportPres', 0, 0, 0, 5)";
	$r=safe_w_sql($q,false,array(1060));

	db_save_version('2013-02-01 12:37:00');
}

if($version<'2013-02-06 20:00:00') {
	$q="ALTER TABLE `Qualifications` ADD `QuBacknoPrinted` DATETIME NOT NULL AFTER `QuTargetNo` ";
	$r=safe_w_sql($q,false,array(1060));
	db_save_version('2013-02-06 20:00:00');
}

if($version<'2013-02-21 21:30:00') {
	$q="ALTER TABLE  `LookUpEntries` CHANGE  `LueCtrlCode`  `LueCtrlCode` DATE NULL DEFAULT NULL ";
	$r=safe_w_sql($q,false,array(1060));

	db_save_version('2013-02-21 21:30:00');
}

if($version<'2013-03-14 10:09:00') {
	// Adds the subrule definition
	$q="replace into LookUpPaths SET LupPath = 'http://www.asta-sbv.ch/var/ianseo/IanseoData.php', LupIocCode = 'SUI'";
	$r=safe_w_sql($q,false,array(1061,1091));

	db_save_version('2013-03-14 10:09:00');
}

if($version<'2013-04-01 16:21:00') {
	safe_w_sql("insert into TourTypes set TtId=23, TtType='Type_Bel_25m_Out', TtDistance=2, TtOrderBy=23 on duplicate key update TtType='Type_Bel_25m_Out', TtDistance=2, TtOrderBy=23");
	safe_w_sql("insert into TourTypes set TtId=24, TtType='Type_Bel_50-30_Out', TtDistance=2, TtOrderBy=24 on duplicate key update TtType='Type_Bel_50-30_Out', TtDistance=2, TtOrderBy=24");
	safe_w_sql("insert into TourTypes set TtId=25, TtType='Type_Bel_50_Out', TtDistance=2, TtOrderBy=25 on duplicate key update TtType='Type_Bel_50_Out', TtDistance=2, TtOrderBy=25");
	safe_w_sql("insert into TourTypes set TtId=26, TtType='Type_Bel_B10_Out', TtDistance=2, TtOrderBy=26 on duplicate key update TtType='Type_Bel_B10_Out', TtDistance=2, TtOrderBy=26");
	safe_w_sql("insert into TourTypes set TtId=27, TtType='Type_Bel_B15_Out', TtDistance=2, TtOrderBy=27 on duplicate key update TtType='Type_Bel_B15_Out', TtDistance=2, TtOrderBy=27");
	safe_w_sql("insert into TourTypes set TtId=28, TtType='Type_Bel_B25_Out', TtDistance=2, TtOrderBy=28 on duplicate key update TtType='Type_Bel_B25_Out', TtDistance=2, TtOrderBy=28");
	safe_w_sql("insert into TourTypes set TtId=29, TtType='Type_Bel_B50-30_Out', TtDistance=2, TtOrderBy=29 on duplicate key update TtType='Type_Bel_B50-30_Out', TtDistance=2, TtOrderBy=29");
	safe_w_sql("insert into TourTypes set TtId=30, TtType='Type_Bel_BFITA_Out', TtDistance=4, TtOrderBy=30 on duplicate key update TtType='Type_Bel_BFITA_Out', TtDistance=4, TtOrderBy=30");

	db_save_version('2013-04-01 16:21:00');
}

if($version<'2013-04-03 12:00:00') {
	$q="ALTER TABLE `Finals` ADD `FinConfirmed` int(4) not null";
	$r=safe_w_sql($q,false,array(1060));
	$q="ALTER TABLE `TeamFinals` ADD `TfConfirmed` int(4) not null";
	$r=safe_w_sql($q,false,array(1060));

	db_save_version('2013-04-03 12:00:00');
}

if($version<'2013-04-05 18:56:00') {
	$q="drop view EventCategories";
	$r=safe_w_sql($q,false,array(1060));
	$q="create view EventCategories as select * from `EventClass` inner join `Events` on EvCode = EcCode and if(EvTeamEvent=0, EcTeamEvent=0, EcTeamEvent>0) and EvTournament = EcTournament";
	$r=safe_w_sql($q,false,array(1060));

	db_save_version('2013-04-05 18:56:00');
}

if($version<'2013-04-19 12:15:00') {
	$q="ALTER TABLE  `Awards` CHANGE  `AwAwarders`  `AwAwarders` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
	$r=safe_w_sql($q,false,array(1060));

	db_save_version('2013-04-19 12:15:00');
}

if($version<'2013-04-24 14:15:00') {
	$q="REPLACE INTO `TourTypes` (`TtId`, `TtType`, `TtDistance`, `TtOrderBy`) VALUES (31, 'Type_ITA_Sperimental', 2, 31)";
	$r=safe_w_sql($q,false,array(1060));

	$q="REPLACE INTO `LookUpPaths` (`LupIocCode`, `LupFors`, `LupPath`, `LupPhotoPath`, `LupFlagsPath`, `LupLastUpdate`) VALUES
		('ITA_e', '', 'http://www.fitarco-italia.org/gare/ianseo/IanseoDataEsordienti.php', 'http://www.fitarco-italia.org/gare/ianseo/IanseoPhoto.php', 'http://www.fitarco-italia.org/gare/ianseo/IanseoFlags.php', '0000-00-00 00:00:00')";
	$r=safe_w_sql($q,false,array(1060));

	$q="REPLACE INTO `LookUpPaths` (`LupIocCode`, `LupFors`, `LupPath`, `LupPhotoPath`, `LupFlagsPath`, `LupLastUpdate`) VALUES
		('ITA_p', '', 'http://www.fitarco-italia.org/gare/ianseo/IanseoDataPinocchio.php', 'http://www.fitarco-italia.org/gare/ianseo/IanseoPhoto.php', 'http://www.fitarco-italia.org/gare/ianseo/IanseoFlags.php', '0000-00-00 00:00:00')";
	$r=safe_w_sql($q,false,array(1060));

	db_save_version('2013-04-24 14:15:00');

}

if($version<'2013-05-02 08:11:01') {
	$q="ALTER TABLE `Tournament` ADD `ToOnlineId` int(10) NOT NULL DEFAULT '0' AFTER `ToId` ";
	$r=safe_w_sql($q,false,array(1060));
	$q="ALTER TABLE `Entries` ADD `EnOnlineId` int(10) NOT NULL DEFAULT '0' AFTER `EnId` ";
	$r=safe_w_sql($q,false,array(1060));
	$q="ALTER TABLE `Countries` ADD `CoOnlineId` int(10) NOT NULL DEFAULT '0' AFTER `CoId` ";
	$r=safe_w_sql($q,false,array(1060));

	db_save_version('2013-05-02 08:11:01');
}

if($version<'2013-05-02 12:11:00') {
	$r=safe_w_sql("ALTER TABLE  CasGrid ENGINE = MYISAM ",false,array(1060));
	$r=safe_w_sql("ALTER TABLE  CasScore ENGINE = MYISAM ",false,array(1060));
	$r=safe_w_sql("ALTER TABLE  CasTeam ENGINE = MYISAM ",false,array(1060));
	$r=safe_w_sql("ALTER TABLE  CasTeamFinal ENGINE = MYISAM ",false,array(1060));
	$r=safe_w_sql("ALTER TABLE  CasTeamTarget ENGINE = MYISAM ",false,array(1060));

	db_save_version('2013-05-02 12:11:00');
}

if($version<'2013-06-13 11:00:00') {
	$q="update `Flags`
set FlSVG=0x7d90cb6e83400c45f7fd0acbdd82c7f30ad32a93acc2aebbf6035020804420821154fdfa0eb4ddf4b5b174afceb125ef8fafd70ee66a9cdaa1f7288911aafe3c946d5f7b7c79ce5387c7c3dd7e9a6b88643f796c42b83d0ab12c0b2d9a86b1168a9945241096b60c4d5c130b84a66aeb2678b431c41563750e7069bbcee3bdd20f27b3fb8717df840df895d699fd419f3266a9ff10a4fa106e4568be843ccf114a8f4fd6185289624baeb08674b20e6090090327ce9129768a6cb28ead96c0e95a5f53a9c9c1aa77ce904c55463ab50a324976de90ee33bd6de7d7871dde01
, FlJPG=0x2f396a2f34414151536b5a4a5267414241514541534142494141442f32774244414149424151454241514942415145434167494341675144416749434167554542414d4542675547426759464267594742776b494267634a427759474341734943516f4b43676f4b4267674c4441734b44416b4b4367722f327742444151494341674943416755444177554b4277594843676f4b43676f4b43676f4b43676f4b43676f4b43676f4b43676f4b43676f4b43676f4b43676f4b43676f4b43676f4b43676f4b43676f4b43676f4b43676f4b4367722f774141524341426b414d67444152454141684542417845422f385141484141424141494441414d414141414141414141414141414141674a4267634b41514d462f3851414d784141415149454177634441775143417741414141414141414d4541514947427755496c52455847565a583074514a456a45544956454b46434a4346544e426359482f7841415a4151454141774542414141414141414141414141414141414267634a42416a2f7841416e45514541414149474377414141414141414141414141414141674d42424264546b74494642676356466c4a566b5a5452302f2f61414177444151414345514d5241443841673255573349414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414145427438643365716c536134343779356431364d75494d4d50706a686166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a7a664864337170556d754f4f386272305a6351595966526166744c3633572f4a6e5a324f4865677741414141414141414141414d3679375a5a4d775762613562577a32576d7a2b505672557275574d3665465941776d586e6b546847454a6c564a6f6678525368474d50636f70475753586244624741457237736670322f5558734e5479474b58796357686f7a456e53454657744e565a65366e384f664c512f4576376831496a4e48623976737073322f38414945506273576175665932715a714d757452727242332f302f716f53726532644a306c474d5951575157546a4d6d34536a4745595156546d6d6b6d3252325452417867414141414141414141414141414141414141414141414141396a4e6d367842326b77597470316c31314a5530555570597a54547a7a5232516c6843487a474d59374e674852586d2b716d6e763074336f36305a59374c566865484e387846353452537143746c477961713662704a43525445486b735a6f5267704931693453624e6b342f776c697442574d4a7076715155446e6d72717661347568562b4958427556574f4b56426a324c4f5a6e474b597a6a5439523036647254664b6969716b597a547a522f4d59786942382b624563516d772b58435a6e793057736930565a473056592f546c556a4345497a776c2b49545268434549782b59776844384157325a377630752b6266476271322b727230364c5352716568376e5554682b4d4f3236324d746d71644b34684d31516d6449724b4f564a4966516e6d5569716a37647332794d36634a592f536847634b30733175585a396c5076766a325872484c6b303156574d5575342f5a592f6946494f6c5847486f503566397a564e5a524e4f4b30794d32314b65614576732b704a5043574d3073734a35673130414141414141414141414141414141414141414141426b46703677613238756e544e66766d55584b474231417978425a7443454e7173694b38696b5a50762b5953375038413043397a3961546237453772326e7934357a72634f7638414d55497133784c4434347330327a49537878424a6f385971776a442b717953433859522b332b715838675542675331394d483157492b6d76574d745377795557647564746451572f7956613031395447576678396d6237624e2b316a39766e36552f3841304230355a7766575139504f78464555665a764e706d4c6657777832394e746f3467795777464e793564553833654e6f53534f4a6e445a47655a745043645365434b3079634a597a7435356f776c684a45446b677a61324a595a634c2f77425132717747376c4f312f677a4e354658414b33705846456e624c4732436e386b585573366338304a4a357059374645706f2b394a5357644f6237795241317541414141414141414141414141414141414141414141417462394b2f31364c4630316c4366656c5436754e724d51722b7875494d34737343782f4430347234685479455a2f664968504a37705a314555564e69694b715530466d385a595379537153776b6c544455575a4c30792f537652654f62685a512f57377473366f39654d56326d45584770584847324d736b6f2f65435579624e67724f356e68442b30454564736636512b514958585a702b30464b347a44414c543344784772556b49782f64592b3477574f484e6c70766a3274305a3535315a6b2f7474677172394b61623362496f796533624d486d2b64397270356b626b764c73336b7178786a4f4e76554737655a797648374a4e32364d694464424f58346b545453546b546c6c68385379772b59375967596941414141414141414141414141414141414141414141414141414141414141414141414141414141466976434c7933633756787154507843433854312f6c683755356e6e693172574f366c59592f6f6349764c647a745847704d2f4548453966355965314f597461316a75705747503648434c79336337567871545078427850582b574874546d4c577459377156686a2b6877693874334f3163616b7a38516354312f6c68375535693172574f366c59592f6f6349764c647a745847704d2f4548453966355965314f597461316a75705747503648434c79336337567871545078427850582b574874546d4c577459377156686a2b6877693874334f3163616b7a38516354312f6c68375535693172574f366c59592f6f6349764c647a745847704d2f4548453966355965314f597461316a75705747503648434c79336337567871545078427850582b574874546d4c577459377156686a2b6877693874334f3163616b7a38516354312f6c68375535693172574f366c59592f6f6349764c647a745847704d2f4548453966355965314f597461316a75705747503648434c79336337567871545078427850582b574874546d4c577459377156686a2b6877693874334f3163616b7a38516354312f6c68375535693172574f366c59592f6f6349764c647a745847704d2f4548453966355965314f597461316a75705747503648434c79336337567871545078427850582b574874546d4c577459377156686a2b6877693874334f3163616b7a38516354312f6c68375535693172574f366c59592f6f6349764c647a745847704d2f4548453966355965314f597461316a75705747503648434c79336337567871545078427850582b574874546d4c577459377156686a2b6877693874334f3163616b7a38516354312f6c68375535693172574f366c59592f6f6349764c647a745847704d2f4548453966355965314f597461316a75705747503648434c79336337567871545078427850582b574874546d4c577459377156686a2b6877693874334f3163616b7a38516354312f6c68375535693172574f366c59592f6f6349764c647a745847704d2f4548453966355965314f597461316a75705747503648434c79336337567871545078427850582b574874546d4c577459377156686a2b6877693874334f3163616b7a38516354312f6c68375535693172574f366c59592f6f6c4f5231567741414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141412f2f5a
, FlEntered='2013-06-13 08:17:14'
where FlTournament=-1
and FlCode='LBA'";
	$r=safe_w_sql($q,false,array(1060));

	db_save_version('2013-06-13 11:00:00');
}

if($version<'2013-07-08 15:00:00') {
	$q="insert into Flags (select FlTournament, FlIocCode, 'TTO', FlSVG, FlJPG, FlEntered, FlChecked, FlContAssoc from Flags where FlIocCode='FITA' and FlCode='TRI')";
	$r=safe_w_sql($q,false,array(1060));

	db_save_version('2013-07-08 15:00:00');
}
if($version<'2013-07-21 18:05:00') {
	$q="REPLACE INTO `LookUpPaths` (`LupIocCode`, `LupFors`, `LupPath`, `LupPhotoPath`, `LupFlagsPath`, `LupLastUpdate`) VALUES
		('NOR', '', 'http://nor.service.ianseo.net/IanseoData.php', '', '', ''),
		('NOR_s', '', 'http://nor.service.ianseo.net/IanseoData.php?ScoreClass=1', '', '', '')";
	$r=safe_w_sql($q,false,array());
	db_save_version('2013-07-21 18:05:00');
}

if($version<'2013-12-19 12:30:00') {
	$q="drop table if exists `DistanceInformation`";
	$r=safe_w_sql($q,false,array(1060));
	$q="CREATE TABLE if not exists `DistanceInformation` (
			`DiTournament` int(10) NOT NULL,
			`DiSession` tinyint(3) NOT NULL,
			`DiDistance` tinyint(3) NOT NULL,
			`DiEnds` tinyint(3) NOT NULL,
			`DiArrows` tinyint(3) NOT NULL,
			`DiMaxpoints` int(11) NOT NULL,
			`DiStart` datetime NOT NULL,
			`DiEnd` datetime NOT NULL,
			`DiWarmup` datetime NOT NULL,
			`DiOptions` text NOT NULL,
			PRIMARY KEY (`DiTournament`,`DiSession`,`DiDistance`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8";
	$r=safe_w_sql($q,false,array(1060));

	db_save_version('2013-12-19 12:30:00');
}
