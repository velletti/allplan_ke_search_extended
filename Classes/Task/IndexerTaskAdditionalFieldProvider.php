<?php
namespace Allplan\AllplanKeSearchExtended\Task;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\LanguageUtility;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Provides additional fields for scheduler tasks
 */
class IndexerTaskAdditionalFieldProvider extends AbstractAdditionalFieldProvider implements AdditionalFieldProviderInterface
{

	/**
	 * Public functions
	 * =================================================================================================================
	 */

	/**
	 * Mandatory function forced by AdditionalFieldProviderInterface
	 * Gets additional fields to render in the form on adding/editing a task
	 * Returns an associative array:
	 * [
	 * 		'Identifier' => [
	 * 			'fieldId' => [
	 * 				'code' => '...',
	 * 				'label' => '...',
	 * 				'cshKey' => '...',
	 * 				'cshLabel' => '...',
	 * 			],
	 * 		],
	 * ]
	 * @param array $taskInfo Values of the fields from the adding/editing task form
	 * @param IndexerTask $task The task object being edited. null when adding a task.
	 * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return array
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
	{

		// Editing a scheduler task
		if ($schedulerModule->getCurrentAction() == 'edit'){

			$indexerTaskConfiguration = $task->getTaskConfiguration();
			$taskInfo['indexerConfigUid'] = $indexerTaskConfiguration->getIndexerConfigUid();
			$taskInfo['sysLanguageUid'] = $indexerTaskConfiguration->getSysLanguageUid();
			$taskInfo['storagePid'] = $indexerTaskConfiguration->getStoragePid();
			$taskInfo['deleteOldEntriesPeriodInDays'] = $indexerTaskConfiguration->getDeleteOldEntriesPeriodInDays();
			$taskInfo['externUrl'] = $indexerTaskConfiguration->getExternUrl();

		}

		$localLangPrefix = 'LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang.xlf:task.indexerTask.';

		return[

			'indexerConfigUid' => [
				'code' => $this->getSelectBox('indexerConfigUid', $this->getIndexerConfigArray(), $taskInfo['indexerConfigUid']),
				'label' => $localLangPrefix . 'indexerConfigUid',
			],
			'sysLanguageUid' => [
				'code' => $this->getSelectBox('sysLanguageUid', $this->getSysLanguageArray(), $taskInfo['sysLanguageUid']),
				'label' => $localLangPrefix . 'sysLanguageUid',
			],
			'storagePid' => [
				'code' => '<input type="text" class="form-control" name="tx_scheduler[storagePid]" value="' . $taskInfo['storagePid'] . '">',
				'label' => $localLangPrefix . 'storagePid',
			],
			'deleteOldEntriesPeriodInDays' => [
				'code' => '<input type="text" class="form-control" name="tx_scheduler[deleteOldEntriesPeriodInDays]" value="' . $taskInfo['deleteOldEntriesPeriodInDays'] . '">',
				'label' => $localLangPrefix . 'deleteOldEntriesPeriodInDays',
			],
			'externUrl' => [
				'code' => '<input type="text" class="form-control" name="tx_scheduler[externUrl]" value="' . $taskInfo['externUrl'] . '">',
				'label' => $localLangPrefix . 'externUrl',
			],

		];

	}

	/**
	 * Mandatory function forced by AdditionalFieldProviderInterface
	 * @param array $submittedData
	 * @param SchedulerModuleController $schedulerModule
	 * @return bool
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
	{

		$errorMessages = [];

		if(empty($submittedData['indexerConfigUid'])){
			$errorMessages[] = 'task.indexerTask.fieldProvider.validation.error.indexerConfigUid';
		}
		if(!empty($submittedData['storagePid']) && !is_numeric($submittedData['storagePid'])){
			$errorMessages[] = 'task.indexerTask.fieldProvider.validation.error.storagePid';
		}
		if(!empty($submittedData['deleteOldEntriesPeriodInDays']) && !is_numeric($submittedData['deleteOldEntriesPeriodInDays'])){
			$errorMessages[] = 'task.indexerTask.fieldProvider.validation.error.deleteOldEntriesPeriodInDays';
		}
		if (!empty($submittedData['externUrl']) && filter_var($submittedData['externUrl'], FILTER_VALIDATE_URL) === FALSE) {
			$errorMessages[] = 'task.indexerTask.fieldProvider.validation.error.externUrl';
		}

		if(!empty($errorMessages)){
			foreach($errorMessages as $errorMessage){
				$this->addMessage(LanguageUtility::translate($errorMessage),AbstractMessage::ERROR);
			}
			return false;
		}

		return true;

	}

	/**
	 * Mandatory function forced by AdditionalFieldProviderInterface
	 * Save the data for the scheduler task, provided by the additional fields
	 * @param array $submittedData
	 * @param AbstractTask|IndexerTask $task
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function saveAdditionalFields(array $submittedData, AbstractTask $task)
	{
		$submittedData = $this->cleanupSubmittedData($submittedData);

		$indexerTaskConfiguration = $task->getTaskConfiguration();

		$indexerTaskConfiguration->setIndexerConfigUid($submittedData['indexerConfigUid']);
		$indexerTaskConfiguration->setSysLanguageUid($submittedData['sysLanguageUid']);
		$indexerTaskConfiguration->setStoragePid($submittedData['storagePid']);
		$indexerTaskConfiguration->setDeleteOldEntriesPeriodInDays($submittedData['deleteOldEntriesPeriodInDays']);
		$indexerTaskConfiguration->setExternUrl($submittedData['externUrl']);

		$task->setTaskConfiguration($indexerTaskConfiguration);

	}

	/**
	 * Private functions
	 * =================================================================================================================
	 */

	/**
	 * Cleans up the submitted data
	 * @param array $submittedData
	 * @return array
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	private function cleanupSubmittedData(array $submittedData): array
	{

		$cleanArray = [];
		foreach($submittedData as $key => $value){
			$cleanArray[$key] = trim($value);
		}

		return $cleanArray;

	}

	/**
	 * Gets a selectbox for the scheduler task form
	 * @param string $fieldName
	 * @param array $values
	 * @param string|null $selectedValue
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	private function getSelectBox(string $fieldName, array $values, ?string $selectedValue = null): string
	{

		$selectBox = '<select name="tx_scheduler[' . $fieldName . ']" class="form-control">';

		foreach($values as $key => $value){

			$selected = '';
			$valuePrefix = '';

			if((string)$key == (string)$selectedValue){
				$selected = 'selected="selected"';
			}
			if($key !== ''){
				$valuePrefix = '[' . $key . '] - ';
			}

			$selectBox.= '<option value="' . $key . '" ' . $selected . '>' . $valuePrefix . $value . '</option>';

		}

		$selectBox.= '</select>';
		return $selectBox;

	}

	/**
	 * Returns the array for the indexer configurations from table tx_kesearch_indexerconfig:
	 * [
	 * 		uid => title,
	 * 		uid => title,
	 * 		uid => title,
	 * ]
	 * @return array|null
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	private function getIndexerConfigArray(): ?array
	{
		$indexerConfigArray = [];
		$records = DbUtility::getAllRecordsFromTable('tx_kesearch_indexerconfig');

		foreach($records as $record){
			$indexerConfigArray[$record['uid']] = $record['title'];
		}

		if(empty($indexerConfigArray)){
			return null;
		}

		return $indexerConfigArray;
	}

	/**
	 * Returns the array for the sys_languages
	 * Adds "Language from indexed record" and "All languages" to the list of sys_languages
	 * @return array|null
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	private function getSysLanguageArray(): ?array
	{

		$defaultLanguage = ['' => LanguageUtility::translate('task.indexerTask.sysLanguageUid.default')];
		$allLanguages = ['-1' => LanguageUtility::translate('task.indexerTask.sysLanguageUid.allLanguages')];
		$sysLanguageArray = LanguageUtility::getSysLanguageRecords();

		// We do not use array_merge here, because then the original keys get lost
		return $defaultLanguage + $allLanguages + $sysLanguageArray;

	}

}
