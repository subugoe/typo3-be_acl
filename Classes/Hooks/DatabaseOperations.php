<?php

namespace Subugoe\BeAcl\Hooks;

class DatabaseOperations
{

    /**
     * @param string $status
     * @param string $table
     * @param $id
     * @param array $fieldArray
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
     */
    public function processDatamap_afterDatabaseOperations($status, $table, $id, array $fieldArray, \TYPO3\CMS\Core\DataHandling\DataHandler $pObj)
    {
        /** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser */
        $backendUser &= $GLOBALS['BE_USER'];

        if ($table == 'pages' && $status == 'new') {
            $backendUser->setAndSaveSessionData('be_acl', []);
            $backendUser->getPagePermsClause(1);
        }

    }
}
