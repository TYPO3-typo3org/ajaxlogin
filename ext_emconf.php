<?php

$EM_CONF[$_EXTKEY] = array(
	'title' => 'AJAX login',
	'description' => '',
	'category' => 'fe',
	'author' => 'Arno Schoon',
	'author_email' => 'arno@maxserv.nl',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'MaxServ B.V.',
	'version' => '1.0.2',
	'constraints' => array(
		'depends' => array(
			'extbase' => '1.3.0-0.0.0',
			'typo3' => '4.5.2-0.0.0'
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:64:{s:9:"ChangeLog";s:4:"0af5";s:10:"README.txt";s:4:"ee2d";s:12:"ext_icon.gif";s:4:"b808";s:14:"ext_tables.php";s:4:"b58c";s:50:"Classes/Scripts/Frontend/Typolink/RawUrlDecode.php";s:4:"fcc8";s:38:"Configuration/TypoScript/constants.txt";s:4:"d41d";s:34:"Configuration/TypoScript/setup.txt";s:4:"3442";s:36:"Resources/Public/css/prettyPhoto.css";s:4:"0acb";s:54:"Resources/Public/images/backgrounds/bodyBackground.jpg";s:4:"ab1d";s:49:"Resources/Public/images/backgrounds/checkmark.gif";s:4:"2d28";s:40:"Resources/Public/images/fullscreen/1.jpg";s:4:"97a4";s:40:"Resources/Public/images/fullscreen/2.jpg";s:4:"8987";s:40:"Resources/Public/images/fullscreen/3.jpg";s:4:"f62c";s:40:"Resources/Public/images/fullscreen/4.jpg";s:4:"81a9";s:40:"Resources/Public/images/fullscreen/5.jpg";s:4:"4f12";s:40:"Resources/Public/images/fullscreen/6.jpg";s:4:"7f08";s:47:"Resources/Public/images/fullscreen/DSC05016.jpg";s:4:"355f";s:47:"Resources/Public/images/fullscreen/DSCF0031.JPG";s:4:"889f";s:43:"Resources/Public/images/fullscreen/high.gif";s:4:"aaad";s:43:"Resources/Public/images/fullscreen/huge.gif";s:4:"1fca";s:43:"Resources/Public/images/fullscreen/wide.gif";s:4:"3c30";s:60:"Resources/Public/images/prettyPhoto/dark_rounded/btnNext.png";s:4:"0a89";s:64:"Resources/Public/images/prettyPhoto/dark_rounded/btnPrevious.png";s:4:"9ad8";s:67:"Resources/Public/images/prettyPhoto/dark_rounded/contentPattern.png";s:4:"2f2d";s:70:"Resources/Public/images/prettyPhoto/dark_rounded/default_thumbnail.gif";s:4:"ed52";s:59:"Resources/Public/images/prettyPhoto/dark_rounded/loader.gif";s:4:"4297";s:59:"Resources/Public/images/prettyPhoto/dark_rounded/sprite.png";s:4:"2724";s:59:"Resources/Public/images/prettyPhoto/dark_square/btnNext.png";s:4:"0a89";s:63:"Resources/Public/images/prettyPhoto/dark_square/btnPrevious.png";s:4:"9ad8";s:66:"Resources/Public/images/prettyPhoto/dark_square/contentPattern.png";s:4:"6660";s:69:"Resources/Public/images/prettyPhoto/dark_square/default_thumbnail.gif";s:4:"ed52";s:58:"Resources/Public/images/prettyPhoto/dark_square/loader.gif";s:4:"4297";s:58:"Resources/Public/images/prettyPhoto/dark_square/sprite.png";s:4:"6b25";s:56:"Resources/Public/images/prettyPhoto/facebook/btnNext.png";s:4:"b8d4";s:60:"Resources/Public/images/prettyPhoto/facebook/btnPrevious.png";s:4:"b251";s:69:"Resources/Public/images/prettyPhoto/facebook/contentPatternBottom.png";s:4:"a6fa";s:67:"Resources/Public/images/prettyPhoto/facebook/contentPatternLeft.png";s:4:"3b7f";s:68:"Resources/Public/images/prettyPhoto/facebook/contentPatternRight.png";s:4:"74bf";s:66:"Resources/Public/images/prettyPhoto/facebook/contentPatternTop.png";s:4:"c2e5";s:66:"Resources/Public/images/prettyPhoto/facebook/default_thumbnail.gif";s:4:"ed52";s:55:"Resources/Public/images/prettyPhoto/facebook/loader.gif";s:4:"df46";s:55:"Resources/Public/images/prettyPhoto/facebook/sprite.png";s:4:"a157";s:61:"Resources/Public/images/prettyPhoto/light_rounded/btnNext.png";s:4:"0a89";s:65:"Resources/Public/images/prettyPhoto/light_rounded/btnPrevious.png";s:4:"9ad8";s:71:"Resources/Public/images/prettyPhoto/light_rounded/default_thumbnail.gif";s:4:"ed52";s:60:"Resources/Public/images/prettyPhoto/light_rounded/loader.gif";s:4:"df46";s:60:"Resources/Public/images/prettyPhoto/light_rounded/sprite.png";s:4:"45b3";s:60:"Resources/Public/images/prettyPhoto/light_square/btnNext.png";s:4:"0a89";s:64:"Resources/Public/images/prettyPhoto/light_square/btnPrevious.png";s:4:"9ad8";s:70:"Resources/Public/images/prettyPhoto/light_square/default_thumbnail.gif";s:4:"ed52";s:59:"Resources/Public/images/prettyPhoto/light_square/loader.gif";s:4:"df46";s:59:"Resources/Public/images/prettyPhoto/light_square/sprite.png";s:4:"6b25";s:49:"Resources/Public/images/thumbnails/flash-logo.png";s:4:"8430";s:53:"Resources/Public/images/thumbnails/quicktime-logo.gif";s:4:"c0bd";s:42:"Resources/Public/images/thumbnails/t_1.jpg";s:4:"d185";s:42:"Resources/Public/images/thumbnails/t_2.jpg";s:4:"a98e";s:42:"Resources/Public/images/thumbnails/t_3.jpg";s:4:"dc71";s:42:"Resources/Public/images/thumbnails/t_4.jpg";s:4:"0ef1";s:42:"Resources/Public/images/thumbnails/t_5.jpg";s:4:"ec98";s:39:"Resources/Public/js/jquery-1.3.2.min.js";s:4:"bb38";s:39:"Resources/Public/js/jquery-1.4.4.min.js";s:4:"67c5";s:41:"Resources/Public/js/jquery.prettyPhoto.js";s:4:"efa2";s:19:"doc/wizard_form.dat";s:4:"c136";s:20:"doc/wizard_form.html";s:4:"2ec0";}',
	'suggests' => array(
	),
);

?>
