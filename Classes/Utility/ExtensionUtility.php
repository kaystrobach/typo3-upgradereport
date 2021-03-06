<?php
/**
 *  Copyright notice
 *
 *  ⓒ 2014 Michiel Roos <michiel@maxserv.nl>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is free
 *  software; you can redistribute it and/or modify it under the terms of the
 *  GNU General Public License as published by the Free Software Foundation;
 *  either version 2 of the License, or (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful, but
 *  WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 *  or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 *  more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * Class Tx_Smoothmigration_Utility_ExtensionUtility
 */
class Tx_Smoothmigration_Utility_ExtensionUtility implements t3lib_Singleton {

	/**
	 * Current TYPO3 LTS version
	 */
	const CURRENT_LTS_VERSION = '6.2.0';

	/**
	 * Get extensions wich claim to be compatible in their ext_emconf.php
	 *
	 * Note that we are ignoring open ended comatibility here. These are the
	 * cases where the version requirements have a 0.0.0 at the end. This is
	 * because we assume that extension creators that care about compatibilty
	 * will specify the maximum supported version instead of providing a 0.0.0
	 * as upper limit.
	 *
	 * @param string $version The version to check against
	 * @param boolean $ignoreOpenEnd Should we ignore open ended requirements?
	 *
	 * @return array Array of compatible extension keys and their version ranges
	 */
	public static function getCompatibleExtensions($version = NULL, $ignoreOpenEnd = TRUE) {
		if ($version === NULL) {
			$version = self::CURRENT_LTS_VERSION;
		}
		$compatibleExtensions = array();
		$extensionList = t3lib_div::makeInstance('tx_em_Extensions_List');
		list($list,) = $extensionList->getInstalledExtensions();
		foreach ($list as $extensionName => $extensionData) {
			if (isset($extensionData['EM_CONF']['constraints']['depends']['typo3'])) {
				$versionRange = tx_em_Tools::splitVersionRange($extensionData['EM_CONF']['constraints']['depends']['typo3']);
				if ((bool)$ignoreOpenEnd) {
					$upperBound = $versionRange[1] !== '0.0.0' && version_compare($version, $versionRange[1], '<=');
				} else {
					$upperBound = $versionRange[1] === '0.0.0' || version_compare($version, $versionRange[1], '<=');
				}
				if (($versionRange[0] === '0.0.0' || version_compare($version, $versionRange[0], '>')) && $upperBound) {
					$compatibleExtensions[$extensionName] = $versionRange;
				}
			}
		}
		return $compatibleExtensions;
	}

	/**
	 * Get extensions wich do not claim to be compatible in their ext_emconf.php
	 *
	 * Note that we are ignoring open ended comatibility here. These are the
	 * cases where the version requirements have a 0.0.0 at the end. This is
	 * because we assume that extension creators that care about compatibilty
	 * will specify the maximum supported version instead of providing a 0.0.0
	 * as upper limit.
	 *
	 * @param string $version The version to check against
	 * @param boolean $ignoreOpenEnd Should we ignore open ended requirements?
	 *
	 * @return array Array of compatible extension keys and their version ranges
	 */
	public static function getIncompatibleExtensions($version = NULL, $ignoreOpenEnd = TRUE) {
		if ($version === NULL) {
			$version = self::CURRENT_LTS_VERSION;
		}
		$extensions = array();
		if (t3lib_div::int_from_ver(TYPO3_version) < 6002000) {
			$extensionList = t3lib_div::makeInstance('tx_em_Extensions_List');
			list($list,) = $extensionList->getInstalledExtensions();
		} else {
			$list = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray();
			// FIXME: work with \TYPO3\CMS\Core\Package\PackageManager::getPackage() etc.
		}
		foreach ($list as $extensionName => $extensionData) {
			if (isset($extensionData['EM_CONF']['constraints']['depends']['typo3'])) {
				$versionRange = tx_em_Tools::splitVersionRange($extensionData['EM_CONF']['constraints']['depends']['typo3']);
				if ((bool)$ignoreOpenEnd) {
					$upperBound = $versionRange[1] !== '0.0.0' && version_compare($version, $versionRange[1], '>');
				} else {
					$upperBound = $versionRange[1] === '0.0.0' || version_compare($version, $versionRange[1], '>');
				}
				if (($versionRange[0] !== '0.0.0' && version_compare($version, $versionRange[0], '<=')) || $upperBound) {
					$extensions[$extensionName] = $versionRange;
				}
			}
		}
		return $extensions;
	}

	/**
	 * Get extensions that have category plugin or category fe
	 *
	 * @param boolean $onlyKeys If true, only the extension keys are returned.
	 *
	 * @return array Array of frontend extension keys
	 */
	public static function getFrontendExtensions($onlyKeys = TRUE) {
		$extensions = array();
		if (t3lib_div::int_from_ver(TYPO3_version) < 6002000) {
			/** @var $extensionList tx_em_Extensions_List */
			$extensionList = t3lib_div::makeInstance('tx_em_Extensions_List');
			$cat = tx_em_Tools::getDefaultCategory();
			$path = PATH_typo3conf . 'ext/';
			$extensionList->getInstExtList($path, $list, $cat, 'L');
		} else {
			$list = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray();
		}
		foreach ($list as $extensionName => $extensionData) {
			if (isset($extensionData['EM_CONF']['category'])) {
				if ((trim($extensionData['EM_CONF']['category']) === 'plugin') ||
					(trim($extensionData['EM_CONF']['category']) === 'fe')
				) {
					if ((bool)$onlyKeys) {
						array_push($extensions, $extensionName);
					} else {
						$extensions[$extensionName] = $extensionData;
					}
				}
			}
		}
		return $extensions;
	}

	/**
	 * Get extensions that are marked as obsolete in their ext_emconf.php
	 *
	 * @param boolean $onlyKeys If true, only the extension keys are returned.
	 *
	 * @return array Array of obsolete extension keys
	 */
	public static function getObsoleteExtensions($onlyKeys = TRUE) {
		$extensions = array();
		if (t3lib_div::int_from_ver(TYPO3_version) < 6002000) {
			$extensionList = t3lib_div::makeInstance('tx_em_Extensions_List');
			list($list,) = $extensionList->getInstalledExtensions();
		} else {
			$list = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray();
		}
		foreach ($list as $extensionName => $extensionData) {
			if (isset($extensionData['EM_CONF']['state'])) {
				if (trim($extensionData['EM_CONF']['state']) === 'obsolete') {
					if ((bool)$onlyKeys) {
						array_push($extensions, $extensionName);
					} else {
						$extensions[$extensionName] = $extensionData;
					}
				}
			}
		}
		return $extensions;
	}

}
