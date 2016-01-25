<?php

namespace Subugoe\BeAcl\Xclass;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 Sebastian Kurfuerst (sebastian@garbage-group.de)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Beuser\Controller\PermissionController;
use TYPO3\CMS\Beuser\Controller\PermissionAjaxController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend ACL - Replacement for "web->Access"
 */
class PermissionModuleController extends PermissionController
{

    /**
     * @var string
     */
    protected $content;

    /**
     * @var array
     */
    protected $aclList;

    /**
     * Showing the permissions in a tree ($this->edit = false)
     * (Adding content to internal content variable)
     */
    public function notEdit()
    {

        // Get ACL configuration
        $beAclConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['be_acl']);
        $disableOldPermissionSystem = 0;
        if ($beAclConfig['disableOldPermissionSystem']) {
            $disableOldPermissionSystem = 1;
        }
        $this->getLanguageService()->includeLLFile('EXT:be_acl/Resources/Private/Language/locallang_perm.php');

        // Get usernames and groupnames: The arrays we get in return contains only 1) users which are members of the groups of the current user, 2) groups that the current user is member of
        $beGroupKeys = $this->getBackendUser()->userGroupsUID;
        $beUserArray = BackendUtility::getUserNames();
        if (!$this->getBackendUser()->isAdmin()) {
            $beUserArray = BackendUtility::blindUserNames($beUserArray, $beGroupKeys, 0);
        }
        $beGroupArray = BackendUtility::getGroupNames();
        if (!$this->getBackendUser()->isAdmin()) {
            $beGroupArray = BackendUtility::blindGroupNames($beGroupArray, $beGroupKeys, 0);
        }

        // Length of strings:
        $tLen = 20;

        // Selector for depth:
        $code = $this->getLanguageService()->getLL('Depth') . ': ';
        $code .= BackendUtility::getFuncMenu($this->id, 'SET[depth]', $this->MOD_SETTINGS['depth'],
            $this->MOD_MENU['depth']);
        $this->content .= $this->doc->section('', $code);

        $tree = $this->initializeTree();

        // Get list of ACL users and groups, and initialize ACLs
        $aclUsers = $this->acl_objectSelector(0, $displayUserSelector, $beAclConfig);
        $aclGroups = $this->acl_objectSelector(1, $displayGroupSelector, $beAclConfig);

        $this->buildACLtree($aclUsers, $aclGroups);

        $this->content .= $displayUserSelector;
        $this->content .= $displayGroupSelector;

        // Make header of table:
        $code = '
			<thead>
				<tr>
					<th colspan="2">&nbsp;</th>
					<th>' . $this->getLanguageService()->getLL('Owner', true) . '</th>';
        $tableCells = [];
        if (!$disableOldPermissionSystem) {
            $tableCells[] = $this->getLanguageService()->getLL('Group', true);
            $tableCells[] = $this->getLanguageService()->getLL('Everybody', true);
            $tableCells[] = $this->getLanguageService()->getLL('EditLock', true);
        }
        // ACL headers
        if (!empty($aclUsers)) {
            $tableCells[] = '<b>' . $this->getLanguageService()->getLL('aclUser') . '</b>';
            foreach ($aclUsers as $uid) {
                $tableCells[] = $beUserArray[$uid]['username'];
            }
        }
        if (!empty($aclGroups)) {
            $tableCells[] = '<b>' . $this->getLanguageService()->getLL('aclGroup') . '</b>';
            foreach ($aclGroups as $uid) {
                $tableCells[] = $beGroupArray[$uid]['title'];
            }
        }
        $code .= $this->printTableHeader($tableCells);
        $code .= '
				</tr>
			</thead>';

        // Traverse tree:
        foreach ($tree->tree as $data) {
            $cells = [];
            $pageId = $data['row']['uid'];

            // Background colors:
            $bgCol = $this->lastEdited == $pageId ? ' class="bgColor-20"' : '';
            $lE_bgCol = $bgCol;

            // User/Group names:
            $userName = $beUserArray[$data['row']['perms_userid']] ?
                $beUserArray[$data['row']['perms_userid']]['username'] :
                ($data['row']['perms_userid'] ? $data['row']['perms_userid'] : '');

            if ($data['row']['perms_userid'] && !$beUserArray[$data['row']['perms_userid']]) {
                $userName = PermissionAjaxController::renderOwnername($pageId, $data['row']['perms_userid'],
                    htmlspecialchars(GeneralUtility::fixed_lgd_cs($userName, 20)), false);
            } else {
                $userName = PermissionAjaxController::renderOwnername($pageId, $data['row']['perms_userid'],
                    htmlspecialchars(GeneralUtility::fixed_lgd_cs($userName, 20)));
            }

            $groupName = $beGroupArray[$data['row']['perms_groupid']] ?
                $beGroupArray[$data['row']['perms_groupid']]['title'] :
                ($data['row']['perms_groupid'] ? $data['row']['perms_groupid'] : '');

            if ($data['row']['perms_groupid'] && !$beGroupArray[$data['row']['perms_groupid']]) {
                $groupName = PermissionAjaxController::renderGroupname($pageId, $data['row']['perms_groupid'],
                    htmlspecialchars(GeneralUtility::fixed_lgd_cs($groupName, 20)), false);
            } else {
                $groupName = PermissionAjaxController::renderGroupname($pageId, $data['row']['perms_groupid'],
                    htmlspecialchars(GeneralUtility::fixed_lgd_cs($groupName, 20)));
            }

            // Seeing if editing of permissions are allowed for that page:
            $editPermsAllowed = $data['row']['perms_userid'] == $this->getBackendUser()->user['uid'] || $this->getBackendUser()->isAdmin();

            // First column:
            $cellAttrib = $data['row']['_CSSCLASS'] ? ' class="' . $data['row']['_CSSCLASS'] . '"' : '';
            $cells[] = '<td align="left" nowrap="nowrap"' . ($cellAttrib ? $cellAttrib : $bgCol) .
                $this->generateTitleAttribute($data['row']['uid'], $beUserArray, $beGroupArray) . '>' .
                $data['HTML'] . htmlspecialchars(GeneralUtility::fixed_lgd_cs($data['row']['title'], $tLen)) . '</td>';

            // "Edit permissions" -icon
            if ($editPermsAllowed && $pageId) {
                $aHref = BackendUtility::getModuleUrl('web_perm') . '&mode=' . $this->MOD_SETTINGS['mode'] . '&depth=' . $this->MOD_SETTINGS['depth'] . '&id=' . ($data['row']['_ORIG_uid'] ? $data['row']['_ORIG_uid'] : $pageId) . '&return_id=' . $this->id . '&edit=1';
                $cells[] = '<td' . $bgCol . '><a href="' . htmlspecialchars($aHref) . '" title="' . $this->getLanguageService()->getLL('ch_permissions',
                        true) . '">' .
                    IconUtility::getSpriteIcon('actions-document-open') . '</a></td>';
            } else {
                $cells[] = '<td' . $bgCol . '></td>';
            }

            if (!$disableOldPermissionSystem) {
                $cells[] = '
				<td' . $bgCol . ' nowrap="nowrap">' . ($pageId ? PermissionAjaxController::renderPermissions($data['row']['perms_user'],
                            $pageId, 'user') . ' ' . $userName : '') . '</td>
				<td' . $bgCol . ' nowrap="nowrap">' . ($pageId ? PermissionAjaxController::renderPermissions($data['row']['perms_group'],
                            $pageId, 'group') . ' ' . $groupName : '') . '</td>
				<td' . $bgCol . ' nowrap="nowrap">' . ($pageId ? ' ' . PermissionAjaxController::renderPermissions($data['row']['perms_everybody'],
                            $pageId, 'everybody') : '') . '</td>
				<td' . $bgCol . ' nowrap="nowrap">' . ($data['row']['editlock'] ? '<span id="el_' . $pageId . '" class="editlock"><a class="editlock" onclick="WebPermissions.toggleEditLock(\'' . $pageId . '\', \'1\');" title="' . $this->getLanguageService()->getLL('EditLock_descr',
                            true) . '">' . IconUtility::getSpriteIcon('status-warning-lock') . '</a></span>' : ($pageId === 0 ? '' : '<span id="el_' . $pageId . '" class="editlock"><a class="editlock" onclick="WebPermissions.toggleEditLock(\'' . $pageId . '\', \'0\');" title="Enable the &raquo;Admin-only&laquo; edit lock for this page">[+]</a></span>')) . '</td>
			';
            }

            // ACL rows
            if (!empty($aclUsers)) {
                $cells[] = '<td' . $bgCol . '>' . $this->countAcls($this->aclList[$data['row']['uid']][0]) . '</td>';
                foreach ($aclUsers as $uid) {
                    $tmpBg = $bgCol;
                    if (isset($this->aclList[$data['row']['uid']][0][$uid]['newAcl'])) {
                        if ($this->aclList[$data['row']['uid']][0][$uid]['recursive']) {
                            $tmpBg = ' class="bgColor5"';
                        } else {
                            $tmpBg = ' class="bgColor6"';
                        }
                    }

                    $cells[] = '<td' . $tmpBg . ' nowrap="nowrap">' . ($data['row']['uid'] ? ' ' . $this->printPerms($this->aclList[$data['row']['uid']][0][$uid]['permissions']) : '') . '</td>';
                }
            }
            if (!empty($aclGroups)) {
                $cells[] = '<td' . $bgCol . '>' . $this->countAcls($this->aclList[$data['row']['uid']][1]) . '</td>';
                foreach ($aclGroups as $uid) {
                    $tmpBg = $bgCol;
                    if (isset($this->aclList[$data['row']['uid']][1][$uid]['newAcl'])) {
                        if ($this->aclList[$data['row']['uid']][1][$uid]['recursive']) {
                            $tmpBg = ' class="bgColor5"';
                        } else {
                            $tmpBg = ' class="bgColor6"';
                        }
                    }
                    $cells[] = '<td' . $tmpBg . ' nowrap="nowrap">' . ($data['row']['uid'] ? ' ' . $this->printPerms($this->aclList[$data['row']['uid']][1][$uid]['permissions']) : '') . '</td>';
                }
            }

            // Compile table row:
            $code .= '<tr>' . implode('', $cells) . '</tr>';
        }

        // Wrap rows in table tags:
        $code = '<table class="t3-table" id="typo3-permissionList">' . $code . '</table>';

        // Adding the content as a section:
        $this->content .= $this->doc->section('', $code);

        // CSH for permissions setting
        $this->content .= BackendUtility::cshItem('xMOD_csh_corebe', 'perm_module', $GLOBALS['BACK_PATH'], '<br />|');

        // Creating legend table:
        $legendText = '<strong>' . $this->getLanguageService()->getLL('1', true) . '</strong>: ' . $this->getLanguageService()->getLL('1_t',
                true);
        $legendText .= '<br /><strong>' . $this->getLanguageService()->getLL('16',
                true) . '</strong>: ' . $this->getLanguageService()->getLL('16_t', true);
        $legendText .= '<br /><strong>' . $this->getLanguageService()->getLL('2',
                true) . '</strong>: ' . $this->getLanguageService()->getLL('2_t', true);
        $legendText .= '<br /><strong>' . $this->getLanguageService()->getLL('4',
                true) . '</strong>: ' . $this->getLanguageService()->getLL('4_t', true);
        $legendText .= '<br /><strong>' . $this->getLanguageService()->getLL('8',
                true) . '</strong>: ' . $this->getLanguageService()->getLL('8_t', true);

        $code = '<div id="permission-information">
					<img' . IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/legend.gif', 'width="86" height="75"') . ' alt="" />
				<div class="text">' . $legendText . '</div></div>';

        $code .= '<div id="perm-legend">' . $this->getLanguageService()->getLL('def', true);
        $code .= '<br /><br />' . IconUtility::getSpriteIcon('status-status-permission-granted') . ': ' . $this->getLanguageService()->getLL('A_Granted',
                true);
        $code .= '<br />' . IconUtility::getSpriteIcon('status-status-permission-denied') . ': ' . $this->getLanguageService()->getLL('A_Denied',
                true);
        $code .= '</div>';

        // Adding section with legend code:
        $this->content .= $this->doc->section($this->getLanguageService()->getLL('Legend') . ':', $code, true, true);
    }

    /**
     * outputs a selector for users / groups, returns current ACLs
     *
     * @param int $type type of ACL. 0 -> user, 1 -> group
     * @param string $displayPointer Pointer where the display code is stored
     * @param array $conf configuration of ACLs
     * @return array list of groups/users where the ACLs will be shown
     */
    public function acl_objectSelector($type, &$displayPointer, $conf)
    {
        $aclObjects = [];

        $res = $this->getDatabaseConnection()->exec_SELECTquery(
            'tx_beacl_acl.object_id AS object_id, tx_beacl_acl.type AS type',
            'tx_beacl_acl, be_groups, be_users',
            'tx_beacl_acl.type=' . intval($type) . ' AND ((tx_beacl_acl.object_id=be_groups.uid AND tx_beacl_acl.type=1) OR (tx_beacl_acl.object_id=be_users.uid AND tx_beacl_acl.type=0))',
            '',
            'be_groups.title ASC, be_users.realname ASC'
        );

        while ($result = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
            $aclObjects[] = $result['object_id'];
        }

        $aclObjects = array_unique($aclObjects);

        // advanced selector disabled
        if (!$conf['enableFilterSelector']) {
            return $aclObjects;
        }

        if (!empty($aclObjects)) {

            // Get usernames and groupnames: The arrays we get in return contains only 1) users which are members of the groups of the current user, 2) groups that the current user is member of
            $groupArray = $this->getBackendUser()->userGroupsUID;
            $be_user_Array = BackendUtility::getUserNames();

            if (!$this->getBackendUser()->isAdmin()) {
                $be_user_Array = BackendUtility::blindUserNames($be_user_Array, $groupArray, 0);
            }

            $be_group_Array = BackendUtility::getGroupNames();

            if (!$this->getBackendUser()->isAdmin()) {
                $be_group_Array = BackendUtility::blindGroupNames($be_group_Array, $groupArray, 0);
            }

            // get current selection from UC, merge data, write it back to UC
            $currentSelection = is_array($this->getBackendUser()->uc['moduleData']['txbeacl_aclSelector'][$type]) ? $this->getBackendUser()->uc['moduleData']['txbeacl_aclSelector'][$type] : [];

            $currentSelectionOverride_raw = GeneralUtility::_GP('tx_beacl_objsel');
            $currentSelectionOverride = [];

            if (is_array($currentSelectionOverride_raw[$type])) {
                foreach ($currentSelectionOverride_raw[$type] as $tmp) {
                    $currentSelectionOverride[$tmp] = $tmp;
                }
            }

            if ($currentSelectionOverride) {
                $currentSelection = $currentSelectionOverride;
            }

            $this->getBackendUser()->uc['moduleData']['txbeacl_aclSelector'][$type] = $currentSelection;
            $this->getBackendUser()->writeUC($this->getBackendUser()->uc);

            // display selector
            $displayCode = '<select size="' . \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(count($aclObjects),
                    5, 15) . '" name="tx_beacl_objsel[' . $type . '][]" multiple="multiple">';
            foreach ($aclObjects as $singleObjectId) {
                if ($type == 0) {
                    $tmpnam = $be_user_Array[$singleObjectId]['username'];
                } else {
                    $tmpnam = $be_group_Array[$singleObjectId]['title'];
                }

                $displayCode .= '<option value="' . $singleObjectId . '" ' . (@in_array($singleObjectId,
                        $currentSelection) ? 'selected' : '') . '>' . $tmpnam . '</option>';
            }

            $displayCode .= '</select>';
            $displayCode .= '<br /><input type="button" value="' . $this->getLanguageService()->getLL('aclObjSelUpdate') . '" onClick="document.editform.action=document.location; document.editform.submit()" /><p />';

            // create section
            switch ($type) {
                case 0:
                    $tmpnam = 'aclUsers';
                    break;
                default:
                    $tmpnam = 'aclGroups';
                    break;
            }
            $displayPointer = $this->doc->section($this->getLanguageService()->getLL($tmpnam, 1), $displayCode);

            return $currentSelection;
        }

        return null;
    }

    /*****************************
     *
     * Helper functions
     *
     *****************************/

    /**
     * returns a datastructure: pageid - userId / groupId - permissions
     *
     * @param array $users user ID list
     * @param array $groups group ID list
     */
    protected function buildACLtree($users, $groups)
    {

        // get permissions in the starting point for users and groups
        $rootLine = BackendUtility::BEgetRootLine($this->id);

        $userStartPermissions = [];
        $groupStartPermissions = [];

        array_shift($rootLine); // needed as a starting point

        foreach ($rootLine as $level => $values) {
            $recursive = ' AND recursive=1';

            $res = $this->getDatabaseConnection()->exec_SELECTquery(
                'type, object_id, permissions',
                'tx_beacl_acl',
                'pid=' . intval($values['uid']) . $recursive
            );

            while ($result = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                if ($result['type'] == 0
                    && in_array($result['object_id'], $users)
                    && !array_key_exists($result['object_id'], $userStartPermissions)
                ) {
                    $userStartPermissions[$result['object_id']] = $result['permissions'];
                } elseif ($result['type'] == 1
                    && in_array($result['object_id'], $groups)
                    && !array_key_exists($result['object_id'], $groupStartPermissions)
                ) {
                    $groupStartPermissions[$result['object_id']] = $result['permissions'];
                }
            }
        }

        foreach ($userStartPermissions as $oid => $perm) {
            $startPerms[0][$oid]['permissions'] = $perm;
            $startPerms[0][$oid]['recursive'] = 1;
        }

        foreach ($groupStartPermissions as $oid => $perm) {
            $startPerms[1][$oid]['permissions'] = $perm;
            $startPerms[1][$oid]['recursive'] = 1;
        }

        $this->traversePageTree_acl($startPerms, $rootLine[0]['uid']);
    }

    /**
     * build ACL tree
     *
     * @param array $parentACLs
     * @param int $uid
     */
    protected function traversePageTree_acl($parentACLs, $uid)
    {
        $res = $this->getDatabaseConnection()->exec_SELECTquery(
            'type, object_id, permissions, recursive',
            'tx_beacl_acl',
            'pid=' . intval($uid)
        );

        $hasNoRecursive = [];
        $this->aclList[$uid] = $parentACLs;

        while ($result = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
            $permissions = [
                'permissions' => $result['permissions'],
                'recursive' => $result['recursive'],
            ];
            if ($result['recursive'] == 0) {
                if ($this->aclList[$uid][$result['type']][$result['object_id']]['newAcl']) {
                    $permissions['newAcl'] = $this->aclList[$uid][$result['type']][$result['object_id']]['newAcl'];
                }
                $this->aclList[$uid][$result['type']][$result['object_id']] = $permissions;
                $permissions['newAcl'] = 1;
                $hasNoRecursive[$uid][$result['type']][$result['object_id']] = $permissions;
            } else {
                $parentACLs[$result['type']][$result['object_id']] = $permissions;
                if (is_array($hasNoRecursive[$uid][$result['type']][$result['object_id']])) {
                    $this->aclList[$uid][$result['type']][$result['object_id']] = $hasNoRecursive[$uid][$result['type']][$result['object_id']];
                } else {
                    $this->aclList[$uid][$result['type']][$result['object_id']] = $permissions;
                }
            }
            $this->aclList[$uid][$result['type']][$result['object_id']]['newAcl'] += 1;
        }

        $res = $this->getDatabaseConnection()->exec_SELECTquery(
            'uid',
            'pages',
            'pid=' . intval($uid) . ' AND deleted=0'
        );

        while ($result = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
            $this->traversePageTree_acl($parentACLs, $result['uid']);
        }
    }

    /**
     * prints table header
     *
     * @param array $cells array of cells
     * @return string HTML output for the cells
     */
    public function printTableHeader($cells)
    {
        $wrappedCells = '';
        foreach ($cells as $singleCell) {
            $wrappedCells .= '<th align="center">' . $singleCell . '</th>';
        }

        return $wrappedCells;
    }

    /**
     * generates title attribute for pages
     *
     * @param int $uid UID of page
     * @param array $be_user_Array BE user array
     * @param array $be_group_Array BE group array
     * @return string HTML: title attribute
     */
    public function generateTitleAttribute($uid, $be_user_Array, $be_group_Array)
    {
        $composedStr = '';
        $this->aclList[$uid];
        if (!$this->aclList[$uid]) {
            return false;
        }
        foreach ($this->aclList[$uid] as $type => $v1) {
            if (!$v1) {
                return false;
            }
            foreach ($v1 as $object_id => $v2) {
                if ($v2['newAcl']) {
                    if ($type == 1) { // group
                        $composedStr .= ' G:' . $be_group_Array[$object_id]['title'];
                    } else {
                        $composedStr .= ' U:' . $be_user_Array[$object_id]['username'];
                    }
                }
            }
        }

        return ' title="' . $composedStr . '"' . ($composedStr ? ' class="bgColor5"' : '');
    }

    public function countAcls($pageData)
    {
        $i = 0;
        if (!$pageData) {
            return '';
        }
        foreach ($pageData as $aclId => $values) {
            if ($values['newAcl']) {
                $i += $values['newAcl'];
            }
        }

        return ($i ? $i : '');
    }

    /**
     * Print a set of permissions
     *
     * @param int $int Permission integer (bits)
     * @return string HTML marked up x/* indications.
     */
    public function printPerms($int)
    {
        $permissions = [
            1,
            16,
            2,
            4,
            8
        ];
        $str = '';

        foreach ($permissions as $permission) {
            if ($int & $permission) {
                $str .= IconUtility::getSpriteIcon('status-status-permission-granted', [
                    'tag' => 'a',
                    'title' => $this->getLanguageService()->getLL($permission, 1),
                    'onclick' => 'WebPermissions.setPermissions(' . $pageId . ', ' . $permission . ', \'delete\', \'' . $who . '\', ' . $int . ');'
                ]);
            } else {
                $str .= IconUtility::getSpriteIcon('status-status-permission-denied', [
                    'tag' => 'a',
                    'title' => $this->getLanguageService()->getLL($permission, 1),
                    'onclick' => 'WebPermissions.setPermissions(' . $pageId . ', ' . $permission . ', \'add\', \'' . $who . '\', ' . $int . ');'
                ]);
            }
        }

        return $str;
    }

    /**
     * Creating form for editing the permissions    ($this->edit = true)
     * (Adding content to internal content variable)
     *
     */
    public function doEdit()
    {
        if ($this->getBackendUser()->workspace != 0) {
            // Adding section with the permission setting matrix:
            $flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                $this->getLanguageService()->getLL('WorkspaceWarningText'), $this->getLanguageService()->getLL('WorkspaceWarning'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING);

            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);

            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }

        // Get ACL configuration
        $disableOldPermissionSystem = 0;
        $beAclConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['be_acl']);
        if ($beAclConfig['disableOldPermissionSystem']) {
            $disableOldPermissionSystem = 1;
        }
        $this->getLanguageService()->includeLLFile('EXT:be_acl/Resources/Private/Language/locallang_perm.php');

        // Get usernames and groupnames
        $beGroupArray = BackendUtility::getListGroupNames('title,uid');
        $beGroupKeys = array_keys($beGroupArray);
        $beUserArray = BackendUtility::getUserNames();
        if (!$this->getBackendUser()->isAdmin()) {
            $beUserArray = BackendUtility::blindUserNames($beUserArray, $beGroupKeys, 1);
        }
        $beGroupArray_o = ($beGroupArray = BackendUtility::getGroupNames());
        if (!$this->getBackendUser()->isAdmin()) {
            $beGroupArray = BackendUtility::blindGroupNames($beGroupArray_o, $beGroupKeys, 1);
        }

        // Set JavaScript
        // Generate list if record is available on subpages, if yes, enter the id
        $this->content .= '<script src="../../../' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('be_acl') .
            'Resources/Public/JavaScript/acl.js" type="text/javascript"></script>';

        // Owner selector:
        $options = '';

        // flag: is set if the page-userid equals one from the user-list
        $userset = 0;
        foreach ($beUserArray as $uid => $row) {
            if ($uid == $this->pageinfo['perms_userid']) {
                $userset = 1;
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            $options .= '<option value="' . $uid . '"' . $selected . '>' . htmlspecialchars($row['username']) . '</option>';
        }
        $options = '<option value="0"></option>' . $options;

        // Hide selector if not needed
        $hidden = '';
        if ($disableOldPermissionSystem) {
            $hidden = ' style="display:none;" ';
        }

        $selector = '<select name="data[pages][' . $this->id . '][perms_userid]"' . $hidden . '>' . $options . '</select>';
        if ($disableOldPermissionSystem) {
            $this->content .= $selector;
        } else {
            $this->content .= $this->doc->section($this->getLanguageService()->getLL('Owner'), $selector, true);
        }

        // Group selector:
        $options = '';
        $userset = 0;
        foreach ($beGroupArray as $uid => $row) {
            if ($uid == $this->pageinfo['perms_groupid']) {
                $userset = 1;
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            $options .= '<option value="' . $uid . '"' . $selected . '>' . htmlspecialchars($row['title']) . '</option>';
        }

        // If the group was not set AND there is a group for the page
        if (!$userset && $this->pageinfo['perms_groupid']) {
            $options = '<option value="' . $this->pageinfo['perms_groupid'] . '" selected="selected">' . htmlspecialchars($beGroupArray_o[$this->pageinfo['perms_groupid']]['title']) . '</option>' . $options;
        }
        $options = '<option value="0"></option>' . $options;
        $selector = '<select name="data[pages][' . $this->id . '][perms_groupid]"' . $hidden . '>' . $options . '</select>';
        if ($disableOldPermissionSystem) {
            $this->content .= $selector;
        } else {
            $this->content .= $this->doc->section($this->getLanguageService()->getLL('Group'), $selector, true);
        }

        // Permissions checkbox matrix:
        $code = '
			<input type="hidden" name="pageID" value="' . (int)$this->id . '" />
			<table class="t3-table" id="typo3-permissionMatrix">
				<thead>
					<tr>
						<th></th>
						<th>' . $this->getLanguageService()->getLL('1', true) . '</th>
						<th>' . $this->getLanguageService()->getLL('16', true) . '</th>
						<th>' . $this->getLanguageService()->getLL('2', true) . '</th>
						<th>' . $this->getLanguageService()->getLL('4', true) . '</th>
						<th>' . $this->getLanguageService()->getLL('8', true) . '</th>
						<th>' . str_replace(' ', '<br />', $this->getLanguageService()->getLL('recursiveAcl', 1)) . '</th>
						<th></th>
					</tr>
				</thead>
				<tbody>';

        if (!$disableOldPermissionSystem) {
            $code .= '
					<tr>
						<td><strong>' . $this->getLanguageService()->getLL('Owner', true) . '</strong></td>
						<td>' . $this->printCheckBox('perms_user', 1) . '</td>
						<td>' . $this->printCheckBox('perms_user', 5) . '</td>
						<td>' . $this->printCheckBox('perms_user', 2) . '</td>
						<td>' . $this->printCheckBox('perms_user', 3) . '</td>
						<td>' . $this->printCheckBox('perms_user', 4) . '</td>
						<td></td>
						<td></td>
					</tr>
					<tr>
						<td><strong>' . $this->getLanguageService()->getLL('Group', true) . '</strong></td>
						<td>' . $this->printCheckBox('perms_group', 1) . '</td>
						<td>' . $this->printCheckBox('perms_group', 5) . '</td>
						<td>' . $this->printCheckBox('perms_group', 2) . '</td>
						<td>' . $this->printCheckBox('perms_group', 3) . '</td>
						<td>' . $this->printCheckBox('perms_group', 4) . '</td>
						<td></td>
						<td></td>
					</tr>
					<tr>
						<td><strong>' . $this->getLanguageService()->getLL('Everybody', true) . '</strong></td>
						<td>' . $this->printCheckBox('perms_everybody', 1) . '</td>
						<td>' . $this->printCheckBox('perms_everybody', 5) . '</td>
						<td>' . $this->printCheckBox('perms_everybody', 2) . '</td>
						<td>' . $this->printCheckBox('perms_everybody', 3) . '</td>
						<td>' . $this->printCheckBox('perms_everybody', 4) . '</td>
						<td></td>
						<td></td>
					</tr>';
        }

        // ACL CODE
        $res = $this->getDatabaseConnection()->exec_SELECTquery('*', 'tx_beacl_acl', 'pid=' . (int)$this->id);
        while ($result = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
            $acl_prefix = 'data[tx_beacl_acl][' . $result['uid'] . ']';
            $code .= '
				<tr>
					<td align="right"><select name="' . $acl_prefix . '[type]" onChange="updateUserGroup(' . $result['uid'] . ')"><option value="0" ' . ($result['type'] ? '' : 'selected="selected"') . '>User</option><option value="1" ' . ($result['type'] ? 'selected="selected"' : '') . '>Group</option></select><select name="' . $acl_prefix . '[object_id]"></select></td>
					<td>' . $this->printCheckBox('perms_acl_' . $result['uid'], 1,
                    'data[tx_beacl_acl][' . $result['uid'] . '][permissions]') . '</td>
					<td>' . $this->printCheckBox('perms_acl_' . $result['uid'], 5,
                    'data[tx_beacl_acl][' . $result['uid'] . '][permissions]') . '</td>
					<td>' . $this->printCheckBox('perms_acl_' . $result['uid'], 2,
                    'data[tx_beacl_acl][' . $result['uid'] . '][permissions]') . '</td>
					<td>' . $this->printCheckBox('perms_acl_' . $result['uid'], 3,
                    'data[tx_beacl_acl][' . $result['uid'] . '][permissions]') . '</td>
					<td>' . $this->printCheckBox('perms_acl_' . $result['uid'], 4,
                    'data[tx_beacl_acl][' . $result['uid'] . '][permissions]') . '
						<input type="hidden" name="' . $acl_prefix . '[permissions]" value="' . $result['permissions'] . '" />

						<script type="text/javascript">updateUserGroup(' . $result['uid'] . ', ' . $result['object_id'] . ');
						setCheck("check[perms_acl_' . $result['uid'] . ']","data[tx_beacl_acl][' . $result['uid'] . '][permissions]");
						global_currentACLs[global_currentACLs.length] = ' . $result['uid'] . ' ;
						</script>

					</td>
					<td>
						<input type="hidden" name="' . $acl_prefix . '[recursive]" value="0" />
						<input type="checkbox" name="' . $acl_prefix . '[recursive]" value="1" ' . ($result['recursive'] ? 'checked="checked"' : '') . ' />
					</td>
					<td><a href="#" onClick="deleteACL(' . $result['uid'] . ')"><img ' . IconUtility::skinImg('',
                    'gfx/garbage.gif') . ' alt="' . $this->getLanguageService()->getLL('delAcl', 1) . '" /></a></td>
				</tr>';
        }

        $code .= '
				</tbody>
			</table>
			<br />
			<span id="insertHiddenFields"></span>
			<img ' . IconUtility::skinImg('', 'gfx/garbage.gif') . ' alt="' . $this->getLanguageService()->getLL('delAcl', 1) . '" / id="templateDeleteImage" style="display:none">
			<a href="javascript:addACL()"><img  ' . IconUtility::skinImg('',
                'gfx/new_el.gif') . ' alt="' . $this->getLanguageService()->getLL('addAcl',
                1) . '" />' . $this->getLanguageService()->getLL('addAcl', 1) . '</a><br />

			<input type="hidden" name="data[pages][' . $this->id . '][perms_user]" value="' . $this->pageinfo['perms_user'] . '" />
			<input type="hidden" name="data[pages][' . $this->id . '][perms_group]" value="' . $this->pageinfo['perms_group'] . '" />
			<input type="hidden" name="data[pages][' . $this->id . '][perms_everybody]" value="' . $this->pageinfo['perms_everybody'] . '" />
			' . ($disableOldPermissionSystem ? '' : $this->getRecursiveSelect($this->id, $this->perms_clause)) . '
			<input type="submit" name="submit" value="' . $this->getLanguageService()->getLL('saveAndClose',
                true) . '" />' . '<input type="submit" value="' . $this->getLanguageService()->getLL('Abort',
                true) . '" onclick="' . htmlspecialchars(('jumpToUrl(' . GeneralUtility::quoteJSvalue((BackendUtility::getModuleUrl('web_perm') . '&id=' . $this->id),
                    true) . '); return false;')) . '" />
			<input type="hidden" name="redirect" value="' . htmlspecialchars((BackendUtility::getModuleUrl('web_perm') . '&mode=' . $this->MOD_SETTINGS['mode'] . '&depth=' . $this->MOD_SETTINGS['depth'] . '&id=' . (int)$this->return_id . '&lastEdited=' . $this->id)) . '" />
			' . \TYPO3\CMS\Backend\Form\FormEngine::getHiddenTokenField('tceAction');

        // Adding section with the permission setting matrix:
        $this->content .= $this->doc->section($this->getLanguageService()->getLL('permissions'), $code, true);

        // CSH for permissions setting
        $this->content .= BackendUtility::cshItem('xMOD_csh_corebe', 'perm_module_setting', $GLOBALS['BACK_PATH'],
            '<br /><br />');

        // Adding help text:
        if ($this->getBackendUser()->uc['helpText']) {
            $legendText = '<p><strong>' . $this->getLanguageService()->getLL('1',
                    true) . '</strong>: ' . $this->getLanguageService()->getLL('1_t', true) . '<br />';
            $legendText .= '<strong>' . $this->getLanguageService()->getLL('16',
                    true) . '</strong>: ' . $this->getLanguageService()->getLL('16_t', true) . '<br />';
            $legendText .= '<strong>' . $this->getLanguageService()->getLL('2',
                    true) . '</strong>: ' . $this->getLanguageService()->getLL('2_t', true) . '<br />';
            $legendText .= '<strong>' . $this->getLanguageService()->getLL('4',
                    true) . '</strong>: ' . $this->getLanguageService()->getLL('4_t', true) . '<br />';
            $legendText .= '<strong>' . $this->getLanguageService()->getLL('8',
                    true) . '</strong>: ' . $this->getLanguageService()->getLL('8_t', true) . '</p>';

            $code = $legendText . '<p>' . $this->getLanguageService()->getLL('def', true) . '</p>';

            $this->content .= $this->doc->section($this->getLanguageService()->getLL('Legend', true), $code, true);
        }
    }

    /**
     * Print a checkbox for the edit-permission form
     *
     * @param    string     $checkName   Checkbox name key
     * @param    int     $num   Checkbox number index
     * @param string     $result   Result sting, not mandatory
     * @return    string        HTML checkbox
     */
    public function printCheckBox($checkName, $num, $result = '')
    {
        if (empty($result)) {
            $result = 'data[pages][' . $GLOBALS['SOBE']->id . '][' . $checkName . ']';
        }

        $onClick = 'checkChange(\'check[' . $checkName . ']\', \'' . $result . '\')';

        return '<input type="checkbox" name="check[' . $checkName . '][' . $num . ']" onclick="' . htmlspecialchars($onClick) . '" /><br />';
    }

    /**
     * @return \TYPO3\CMS\Backend\Tree\View\PageTreeView
     */
    protected function initializeTree()
    {

        /** @var \TYPO3\CMS\Backend\Tree\View\PageTreeView $tree */
        $tree = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\View\PageTreeView::class);
        $tree->init('AND ' . $this->perms_clause);
        $tree->addField('perms_user', 1);
        $tree->addField('perms_group', 1);
        $tree->addField('perms_everybody', 1);
        $tree->addField('perms_userid', 1);
        $tree->addField('perms_groupid', 1);
        $tree->addField('hidden');
        $tree->addField('fe_group');
        $tree->addField('starttime');
        $tree->addField('endtime');
        $tree->addField('editlock');

        // Creating top icon; the current page
        $HTML = IconUtility::getSpriteIconForRecord('pages', $this->pageinfo);
        $tree->tree[] = [
            'row' => $this->pageinfo,
            'HTML' => $HTML
        ];

        // Create the tree from $this->id:
        $tree->getTree($this->id, $this->MOD_SETTINGS['depth'], '');
        return $tree;
    }
}
