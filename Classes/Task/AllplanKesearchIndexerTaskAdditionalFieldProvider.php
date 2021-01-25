<?php
namespace Allplan\AllplanKeSearchExtended\Task;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * A task that should be run regularly that deletes
 * datasets flagged as "deleted" from the DB.
 */
class AllplanKesearchIndexerTaskAdditionalFieldProvider extends  AbstractAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface
{


    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param array $taskInfo Values of the fields from the add/edit task form
     * @param \Allplan\AllplanKeSearchExtended\Task\AllplanKesearchIndexerTask $task The task object being edited. NULL when adding a task!
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return array A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        if ($schedulerModule->getCurrentAction() === 'edit') {
            $taskInfo['IndexerCleanerPeriod'] = $task->getPeriod();
            $taskInfo['IndexerLanguage'] = $task->getLanguage();
            $taskInfo['IndexerExternalUrl'] = $task->getExternalUrl();
            $taskInfo['IndexerRowcount'] = $task->getRowcount() ;
            $taskInfo['IndexerStoragePid'] = $task->getStoragePid();

            $taskInfo['IndexerConfigs'] = $task->getConfigs();

            if( is_array($taskInfo['IndexerConfigs'])) {
                // remove deleted Indexer Uid From existing Config
                $rows= $this->getIndexerRecords() ;
                foreach (  $taskInfo['IndexerConfigs'] as $key => $tca ) {
                    $checkTca = false ;
                    foreach ($rows  as $row) {
                        if ( $row['uid'] ==  $tca ) {
                            $checkTca = true;
                        }
                    }
                    if( !$checkTca ) {
                        unset ($taskInfo['IndexerConfigs'][$key]);
                    }
                }
                // $task->setConfigs($taskInfo['IndexerConfigs']);
            }
        }



        $additionalFields['period'] = [
            'code' => '<input type="text" class="form-control" name="tx_scheduler[IndexerCleanerPeriod]" value="' . $taskInfo['IndexerCleanerPeriod'] . '">',
            'label' => 'LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_tasks.xlf:indexerTaskPeriod',
            'cshKey' => '',
            'cshLabel' => 'task_indexerCleaner_selectedPeriod'
        ];
        $additionalFields['rowcount'] = [
            'code' => '<input type="text" class="form-control" name="tx_scheduler[IndexerRowcount]" value="' . $taskInfo['IndexerRowcount'] . '">',
            'label' => 'LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_tasks.xlf:indexerTaskRowcount',
            'cshKey' => '',
            'cshLabel' => 'task_indexerCleaner_selectedRowcount'
        ];


        $additionalFields['tca'] = [
            'code' => $this->getTcaSelectHtml($taskInfo['IndexerConfigs']),
            'label' => 'LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_tasks.xlf:indexerTaskTCA',
            'cshKey' => '',
            'cshLabel' => 'task_indexerCleaner_selectedConfigs'
        ];

        $additionalFields['language'] = [
            'code' => $this->getlanguageSelectHtml($taskInfo['IndexerLanguage']),
            'label' => 'LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_tasks.xlf:indexerTaskLang',
            'cshKey' => '',
            'cshLabel' => 'task_indexerCleaner_selectedLanguage'
        ];
        $additionalFields['external'] = [
            'code' => '<input type="text" class="form-control" name="tx_scheduler[IndexerExternalUrl]" value="' . $taskInfo['IndexerExternalUrl'] . '">',
            'label' => 'LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_tasks.xlf:indexerTaskExternalUrl',
            'cshKey' => '',
            'cshLabel' => 'task_indexerCleaner_selectedExternalUrl'
        ];
        $additionalFields['storagepid'] = [
            'code' => '<input type="text" class="form-control" name="tx_scheduler[IndexerStoragePid]" value="' . $taskInfo['IndexerStoragePid'] . '">',
            'label' => 'LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_tasks.xlf:indexerTaskStoragePid',
            'cshKey' => '',
            'cshLabel' => 'task_indexerCleaner_selectedStoragePid'
        ];

        return $additionalFields;
    }

    /**
     * Gets the select-box from the TCA-fields
     *
     * @param array $selectedTables
     * @return string
     */
    protected function getTcaSelectHtml($selectedConfigs = [])
    {
        if (!is_array($selectedConfigs)) {
            $selectedConfigs = [];
        } else {
            $temp = array() ;
            foreach ($selectedConfigs as $key => $value) {
                $temp[] = intval( $value ) ;
            }
            $selectedConfigs = $temp ;
        }
        $dataPrev = implode("-" , $selectedConfigs ) ;
        $tcaSelectHtml = '<select name="tx_scheduler[IndexerConfigs][]" multiple="multiple" class="form-control" size="10" title="Previous: ' . $dataPrev . '">';

        $options = [];
        $rows= $this->getIndexerRecords() ;

        foreach ($rows as $key => $row) {
                $selected = in_array($row['uid'], $selectedConfigs ) ? ' selected="selected" ' : '';

                $tableTitle = $row['title'] ;
                $options[$key] = '<option' . $selected . ' value="' . $row['uid'] . '">' . ' (' . $row['uid'] . ') - ' . htmlspecialchars($tableTitle  ) . '</option>';
        }
        ksort($options);

        $tcaSelectHtml .= implode('', $options);
        $tcaSelectHtml .= '</select>';

        return $tcaSelectHtml;
    }

    /**
     * Gets the select-box from the TCA-fields
     *
     * @param array $selectedTables
     * @return string
     */
    protected function getlanguageSelectHtml($selectedConfigs = [])
    {
        if (!is_array($selectedConfigs)) {
            $selectedConfigs = [];
        } else {
            $temp = array() ;
            foreach ($selectedConfigs as $key => $value) {
                $temp[] = intval( $value ) ;
            }
            $selectedConfigs = $temp ;
        }
        $SelectHtml = '<select name="tx_scheduler[IndexerLanguage][]" class="form-control" size="10">';

        $options = [];
        $rows= $this->getLanguageRecords() ;
        $selected = in_array("-1" , $selectedConfigs ) ? ' selected="selected"' : '';
        $options[] = '<option value=""> - use language from indexed data entry (default)</option>';
        $options[] = '<option' . $selected . ' value="-1">(-1) - All Languages </option>';

        $selected = in_array("0" , $selectedConfigs ) ? ' selected="selected"' : '';
        $options[] = '<option' . $selected . ' value="0">(0) - Default Language  </option>';
        if( count( $rows ) > 0 ) {
            foreach ($rows as $key => $row) {
                if( $row['uid']  > 0 ) {
                    $selected = in_array($row['uid'], $selectedConfigs ) ? ' selected="selected"' : '';
                    $tableTitle = $row['title'] ;
                    $options[] = '<option' . $selected . ' value="' . $row['uid'] . '">' . ' (' . $row['uid'] . ') - ' . htmlspecialchars($tableTitle  )  . '</option>';

                }
            }

        }


        $SelectHtml .= implode('', $options);
        $SelectHtml .= '</select>';

        return $SelectHtml;
    }

    protected function getIndexerRecords() {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance( "TYPO3\\CMS\\Core\\Database\\ConnectionPool");

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connectionPool->getConnectionForTable('tx_kesearch_indexerconfig')->createQueryBuilder();
        $indexerRows = $queryBuilder->select('*')
            ->from('tx_kesearch_indexerconfig')->execute()->fetchAll() ;


        if (!count($indexerRows) > 0) {
            return false ;
        }
        return $indexerRows ;
    }


    protected function getLanguageRecords() {
        $return = [] ;
        /** @var SiteFinder $siteFinder */
        $siteFinder = GeneralUtility::makeInstance("TYPO3\\CMS\\Core\\Site\SiteFinder") ;
        $sites = $siteFinder->getAllSites() ;
        if( is_array($sites )) {
            foreach ($sites as $site ) {
                if( $site && is_array( $site->getConfiguration()["languages"]  )) {
                    foreach ( $site->getConfiguration()["languages"] as $id => $lng ) {
                        $uid = $lng['languageId'] ;
                        $return[$uid] = array( 'uid' => $uid , 'title' => $lng['title'] ) ;
                    }
                }
            }
        }
        if (!count($return) > 0) {
            $return = false ;
        }
        return $return ;
    }

    /**
     * Validates the additional fields' values
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $submittedData['IndexerConfigs'] = $this->removeNotValidTcaAndCheck( $submittedData['IndexerConfigs'] ) ;
        $validPeriod = $this->validateAdditionalFieldPeriod($submittedData['IndexerCleanerPeriod'], $schedulerModule);
        $validTca = $this->validateAdditionalFieldTca($submittedData['IndexerConfigs'], $schedulerModule);
        $validStoragePid = $this->validateAdditionalFieldStoragePid($submittedData['IndexerStoragePid'], $schedulerModule);
        return $validPeriod && $validTca && $validStoragePid ;
    }

    /**
     * Validates the selected Tables
     *
     * @param array $tca The given TCA-tables as array
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool TRUE if validation was ok, FALSE otherwise
     */
    protected function validateAdditionalFieldTca($tca, SchedulerModuleController $schedulerModule)
    {
        $tca =  $this->removeNotValidTcaAndCheck($tca, $schedulerModule) ;
        return $this->checkTcaIsNotEmpty($tca, $schedulerModule)  ;

    }

    /**
     * Checks if the array is empty
     *
     * @param array $tca The given TCA-tables as array
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool TRUE if validation was ok, FALSE otherwise
     */
    protected function checkTcaIsNotEmpty($tca, SchedulerModuleController $schedulerModule)
    {
        if (is_array($tca) && count($tca) > 0 ) {
            $validTca = true;
        } else {

            $validTca = false;
        }
        if( $tca === null ) {
            $validTca = false;
        }
        if( !$validTca ) {
            // @extensionScannerIgnoreLine
            $this->addMessage(
                htmlspecialchars( $this->getLanguageService()->sL('LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_tasks.xlf:indexerTaskErrorTCAempty'), ENT_COMPAT, 'UTF-8', false)
                ,
                FlashMessage::ERROR
            );
        }


        return $validTca;
    }

    /**
     * Checks if the given tables are in the TCA
     *
     * @param array $tca The given Indexer IDs as array
     * @return bool TRUE if validation was ok, FALSE otherwise
     */
    protected function removeNotValidTcaAndCheck( $tcas )
    {
        if( !is_array( $tcas)) {
            return $tcas ;
        }
        $checkTca = false  ;
        $rows= $this->getIndexerRecords() ;

        foreach (  $tcas as $key => $tca ) {
            $checkTca = false ;
            foreach ($rows  as $row) {
                if ( $row['uid'] ==  $tca ) {
                    $checkTca = true;
                }
            }
            if( !$checkTca ) {
                unset ($tcas[$key]);
            }
        }
        return $tcas ;
    }

    /**
     * Validates the input of period
     *
     * @param int $period The given period as integer
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool TRUE if validation was ok, FALSE otherwise
     */
    protected function validateAdditionalFieldPeriod($period, SchedulerModuleController $schedulerModule)
    {
        if (!empty($period) && ( filter_var($period, FILTER_VALIDATE_INT) !== false || intval( $period ) == -1 )) {
            $validPeriod = true;
        } else {
            // @extensionScannerIgnoreLine
            $this->addMessage(
                htmlspecialchars( $this->getLanguageService()->sL('LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_tasks.xlf:indexerTaskErrorPeriod'), ENT_COMPAT, 'UTF-8', false)
                ,
                FlashMessage::ERROR
            );
            $validPeriod = false;
        }

        return $validPeriod;
    }

    /**
     * Validates the input of period
     *
     * @param int $period The given period as integer
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool TRUE if validation was ok, FALSE otherwise
     */
    protected function validateAdditionalFieldStoragePid($storagePid, SchedulerModuleController $schedulerModule)
    {
        if (empty($period) ||   filter_var($period, FILTER_VALIDATE_INT) !== false  ) {
            $validPeriod = true;
        } else {
            // @extensionScannerIgnoreLine
            $this->addMessage(
                htmlspecialchars( $this->getLanguageService()->sL('LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_tasks.xlf:indexerTaskErrorStoragePid'), ENT_COMPAT, 'UTF-8', false) ,

                FlashMessage::ERROR
            );
            $validPeriod = false;
        }

        return $validPeriod;
    }

    /**
     * Takes care of saving the additional fields' values in the task's object
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param AbstractTask $task Reference to the scheduler backend module
     * @return void
     * @throws \InvalidArgumentException
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        if (!$task instanceof AbstractTask ) {
            throw new \InvalidArgumentException(
                'Expected a task of type \ALLPLAN\AllplanKeSearchExtended\Task\AllplanKesearchIndexerTask, but got ' . get_class($task),
                1329219449
            );
        }
        $task->setConfigs($submittedData['IndexerConfigs']);
        $task->setPeriod($submittedData['IndexerCleanerPeriod']);
        $task->setLanguage($submittedData['IndexerLanguage']);
        $task->setExternalUrl($submittedData['IndexerExternalUrl']);
        $task->setRowcount($submittedData['IndexerRowcount']);
        $task->setStoragePid($submittedData['IndexerStoragePid']);
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }




}
