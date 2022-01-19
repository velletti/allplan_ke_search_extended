<?php
namespace Allplan\AllplanKeSearchExtended\Task;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Php
 */
use InvalidArgumentException;

class AllplanKesearchIndexerTaskAdditionalFieldProvider extends  AbstractAdditionalFieldProvider implements AdditionalFieldProviderInterface
{

	/**
	 * Gets additional fields to render in the form to add/edit a task
	 *
	 * @param array $taskInfo Values of the fields from the add/edit task form
	 * @param AllplanKesearchIndexerTask $task The task object being edited. NULL when adding a task!
	 * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return array An associative array: array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
	 */
	public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
	{

		if ($schedulerModule->getCurrentAction() == 'edit'){

			$taskInfo['IndexerCleanerPeriod'] = $task->getPeriod();
			$taskInfo['IndexerLanguage'] = $task->getLanguage();
			$taskInfo['IndexerExternalUrl'] = $task->getExternalUrl();
			$taskInfo['IndexerRowcount'] = $task->getRowcount() ;
			$taskInfo['IndexerStoragePid'] = $task->getStoragePid();
			$taskInfo['IndexerConfigs'] = $task->getConfigs();

			if(is_array($taskInfo['IndexerConfigs'])){
				// remove deleted indexer uid from existing config
				$rows= $this->getIndexerRecords();
				foreach($taskInfo['IndexerConfigs'] as $key => $tca){
					$checkTca = false;
					foreach ($rows  as $row){
						if($row['uid'] ==  $tca){
							$checkTca = true;
						}
					}
					if(!$checkTca){
						unset ($taskInfo['IndexerConfigs'][$key]);
					}
				}
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
			'code' => $this->getLanguageSelectHtml($taskInfo['IndexerLanguage']), // Todo: check IDE error
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
	 * @param array $selectedConfigs
	 * @return string
	 */
	protected function getTcaSelectHtml($selectedConfigs = []): string
	{
		if (!is_array($selectedConfigs)){
			$selectedConfigs = [];
		} else {
			$temp = [];
			foreach ($selectedConfigs as $value){
				$temp[] = intval($value);
			}
			$selectedConfigs = $temp;
		}

		$dataPrev = implode('-', $selectedConfigs);
		$tcaSelectHtml = '<select name="tx_scheduler[IndexerConfigs][]" multiple="multiple" class="form-control" size="10" title="Previous: ' . $dataPrev . '">';
		$options = [];
		$rows= $this->getIndexerRecords();

		foreach ($rows as $key => $row){
			$selected = in_array($row['uid'], $selectedConfigs) ? ' selected="selected" ' : '';
			$tableTitle = $row['title'] ;
			$options[$key] = '<option' . $selected . ' value="' . $row['uid'] . '">' . ' (' . $row['uid'] . ') - ' . htmlspecialchars($tableTitle  ) . '</option>';
		}
		ksort($options);

		$tcaSelectHtml.= implode('', $options);
		$tcaSelectHtml.= '</select>';

		return $tcaSelectHtml;

	}

	/**
	 * Gets the select-box from the TCA-fields
	 * @param array $selectedConfigs
	 * @return string
	 */
	protected function getLanguageSelectHtml(array $selectedConfigs = []): string
	{
		if (!is_array($selectedConfigs)){
			$selectedConfigs = [];
		} else {
			$temp = [];
			foreach ($selectedConfigs as $value){
				$temp[] = intval($value);
			}
			$selectedConfigs = $temp;
		}
		$selectHtml = '<select name="tx_scheduler[IndexerLanguage][]" class="form-control" size="10">';
		$options = [];
		$rows= $this->getLanguageRecords();

		$selected = in_array('-1' , $selectedConfigs) ? ' selected="selected"' : '';
		$options[] = '<option value="">Language from indexed data entry (default)</option>';
		$options[] = '<option' . $selected . ' value="-1">All Languages (-1)</option>';

		$selected = in_array('0', $selectedConfigs) ? ' selected="selected"' : '';
		$options[] = '<option' . $selected . ' value="0">Default language (0)</option>';

		if(count($rows) > 0){
			foreach ($rows as $row){
				if($row['uid']  > 0){
					$selected = in_array($row['uid'], $selectedConfigs) ? ' selected="selected"' : '';
					$tableTitle = $row['title'];
					$options[] = '<option' . $selected . ' value="' . $row['uid'] . '">' . ' (' . $row['uid'] . ') - ' . htmlspecialchars($tableTitle) . '</option>';
				}
			}
		}

		$selectHtml .= implode('', $options);
		$selectHtml .= '</select>';
		
		return $selectHtml;

	}

	/**
	 * @return false|array
	 */
	protected function getIndexerRecords()
	{
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$queryBuilder = $connectionPool->getConnectionForTable('tx_kesearch_indexerconfig')->createQueryBuilder();
		$indexerRows = $queryBuilder
			->select('*')
			->from('tx_kesearch_indexerconfig')
			->execute()
			->fetchAll()
		;

		if (!count($indexerRows) > 0){
			return false;
		}

		return $indexerRows;
	}

	/**
	 * @return array|false
	 */
	protected function getLanguageRecords()
	{
		$return = [];
		$siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
		$sites = $siteFinder->getAllSites();
		if(is_array($sites)){
			foreach ($sites as $site){
				if($site && is_array($site->getConfiguration()['languages'])){
					foreach($site->getConfiguration()['languages'] as $language){
						$uid = $language['languageId'];
						$return[$uid] = ['uid' => $uid, 'title' => $language['title']];
					}
				}
			}
		}
		if (!count($return) > 0){
			$return = false;
		}
		return $return;
	}

	/**
	 * Validates the additional fields values
	 * @param array $submittedData An array containing the data submitted by the add/edit task form
	 * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return bool true, if validation was ok (or selected class is not relevant)
	 */
	public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
	{
		$submittedData['IndexerConfigs'] = $this->removeNotValidTcaAndCheck( $submittedData['IndexerConfigs']);
		$validPeriod = $this->validateAdditionalFieldPeriod($submittedData['IndexerCleanerPeriod'], $schedulerModule);
		$validTca = $this->validateAdditionalFieldTca($submittedData['IndexerConfigs'], $schedulerModule);
		$validStoragePid = $this->validateAdditionalFieldStoragePid($submittedData['IndexerStoragePid'], $schedulerModule);
		return $validPeriod && $validTca && $validStoragePid;
	}

	/**
	 * Validates the selected Tables
	 * @param array $tca The given TCA-tables as array
	 * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return bool true, if validation was ok
	 */
	protected function validateAdditionalFieldTca(array $tca, SchedulerModuleController $schedulerModule): bool
	{
		$tca = $this->removeNotValidTcaAndCheck($tca);
		return $this->checkTcaIsNotEmpty($tca, $schedulerModule);
	}

	/**
	 * Checks if the array is empty
	 * @param array|null $tca The given TCA-tables as array
	 * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return bool true if validation was ok
	 */
	protected function checkTcaIsNotEmpty(?array $tca, SchedulerModuleController $schedulerModule): bool
	{
		if (count($tca) > 0){
			$validTca = true;
		} else {
			$validTca = false;
		}
		if($tca === null){
			$validTca = false;
		}
		if(!$validTca){
			// @extensionScannerIgnoreLine
			$this->addMessage(
				htmlspecialchars(
					$this->getLanguageService()->sL('LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_tasks.xlf:indexerTaskErrorTCAempty'),
					ENT_COMPAT,
					'UTF-8',
					false
				),
				AbstractMessage::ERROR
			);
		}
		return $validTca;
	}

	/**
	 * Checks if the given tables are in the TCA
	 * @param array|null $tcaArray The given Indexer IDs as array
	 * @return array
	 */
	protected function removeNotValidTcaAndCheck(?array $tcaArray): ?array
	{
		if( !is_array($tcaArray)){
			return $tcaArray;
		}

		$rows = $this->getIndexerRecords();

		foreach($tcaArray as $key => $tca){
			$checkTca = false;
			foreach ($rows as $row){
				if ($row['uid'] == $tca){
					$checkTca = true;
				}
			}
			if(!$checkTca){
				unset ($tcaArray[$key]);
			}
		}

		return $tcaArray;
	}

	/**
	 * Validates the input of field period
	 * @param string|int $period The given period as integer
	 * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return bool true if validation was ok
	 */
	protected function validateAdditionalFieldPeriod($period, SchedulerModuleController $schedulerModule): bool
	{
		if (!empty($period) && ( filter_var($period, FILTER_VALIDATE_INT) !== false || intval( $period ) == -1 )) {
			$validPeriod = true;
		} else {
			// @extensionScannerIgnoreLine
			$this->addMessage(
				htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_tasks.xlf:indexerTaskErrorPeriod'),ENT_COMPAT,'UTF-8',false),
				FlashMessage::ERROR
			);
			$validPeriod = false;
		}
		return $validPeriod;
	}

	/**
	 * Validates the input of storage period
	 * @param string|int $storagePid The given period as integer
	 * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return bool true if validation was ok
	 */
	protected function validateAdditionalFieldStoragePid($storagePid, SchedulerModuleController $schedulerModule): bool
	{
		if (empty($period) || filter_var($period,FILTER_VALIDATE_INT) !== false) {
			$validPeriod = true;
		} else {
			// @extensionScannerIgnoreLine
			$this->addMessage(
				htmlspecialchars( $this->getLanguageService()->sL('LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_tasks.xlf:indexerTaskErrorStoragePid'),ENT_COMPAT,'UTF-8',false),
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
	 * @param AbstractTask|object|null $task Reference to the scheduler backend module
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function saveAdditionalFields(array $submittedData, $task)
	{
		if (!$task instanceof AbstractTask ) {
			throw new InvalidArgumentException(
				'Expected a task of type AllplanKesearchIndexerTask, but got ' . get_class($task),
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
	 * @return LanguageService;
	 */
	protected function getLanguageService(): LanguageService
	{
		return $GLOBALS['LANG'];
	}

}
