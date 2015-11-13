<?php
if (!defined("TYPO3_MODE")) {
    die ("Access denied.");
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_beacl_acl');

$TCA["tx_beacl_acl"] = [
    "ctrl" => [
        "title" => "LLL:EXT:be_acl/Resources/Private/Language/locallang_db.php:tx_beacl_acl",
        "label" => "uid",
        "tstamp" => "tstamp",
        "crdate" => "crdate",
        "cruser_id" => "cruser_id",
        "type" => "type",
        "default_sortby" => "ORDER BY type",
        "dynamicConfigFile" => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/tca.php',
        "iconfile" => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Images/icon_tx_beacl_acl.gif',
    ],
    "feInterface" => [
        "fe_admin_fieldList" => "type, object_id, permissions, recursive",
    ]
];
