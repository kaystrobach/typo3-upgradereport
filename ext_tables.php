<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
	// avoid that this block is loaded in the frontend or within the upgrade-wizards
if (TYPO3_MODE == 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
	/**
	* Registers a Backend Module
	*/
	Tx_Extbase_Utility_Extension::registerModule(
		$_EXTKEY,
		'tools',
		$_EXTKEY,
		'after:reports',
		array(
				// An array holding the controller-action-combinations that are accessible
			'Report'		=> 'index',
			'Ajax'			=> 'runTest,getResults'
		),
		array(
			'access' => 'user,group',
			'icon'   => 'EXT:' . $_EXTKEY . '/Resources/Public/Images/ModuleIcon.png',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xml'
		)
	);
}

$TCA['tx_upgradereport_domain_model_issue'] = array(
	'ctrl' => array(
		'title' => 'recognized upgrade issues',
		'label' => ($confArr['label']) ? $confArr['label'] : 'check',
		'crdate' => 'crdate',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'cruser_id' => 'cruser_id',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'Configuration/TCA/tx_upgradereport_domain_model_issue.php'
	)
);

	// Add Icons
$icons = array(
//	'sendtonextstage' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Images/version-workspace-sendtonextstage.png',
);
//t3lib_SpriteManager::addSingleIcons($icons, $_EXTKEY);

?>