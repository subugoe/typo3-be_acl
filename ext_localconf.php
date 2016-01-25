<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
    options.saveDocNew.tx_beacl_acl=1
');

$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['calcPerms'][] = 'Subugoe\\BeAcl\\PermissionCalculator->calcPerms';
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getPagePermsClause'][] = 'Subugoe\\BeAcl\\PermissionCalculator->getPagePermsClause';

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\BeUser\Controller\PermissionController::class] = [
    'className' => \Subugoe\BeAcl\Xclass\PermissionModuleController::class,
];

if (TYPO3_MODE === 'BE') {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \Subugoe\BeAcl\Hooks\DatabaseOperations::class;
}
