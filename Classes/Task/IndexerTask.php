<?php
namespace Allplan\AllplanKeSearchExtended\Task;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\IndexerRunner;
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Php
 */
use RuntimeException;

/**
 * Custom task for various Allplan contents
 * Selectable in TYPO3 backend => Scheduler
 */
class IndexerTask extends AbstractTask
{

	/**
	 * Class variables
	 * =================================================================================================================
	 */

	/**
	 * @var IndexerTaskConfiguration
	 */
	protected IndexerTaskConfiguration $taskConfiguration;

	/**
	 * @return IndexerTaskConfiguration
	 */
	public function getTaskConfiguration(): IndexerTaskConfiguration
	{
		return $this->taskConfiguration;
	}

	/**
	 * @param IndexerTaskConfiguration $taskConfiguration
	 */
	public function setTaskConfiguration(IndexerTaskConfiguration $taskConfiguration): void
	{
		$this->taskConfiguration = $taskConfiguration;
	}

	/**
	 * Functions
	 * =================================================================================================================
	 */

	/**
	 * Initialize the task taskConfiguration object and call the parent constructor
	 * @param IndexerTaskConfiguration|null $taskConfiguration
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function __construct(?IndexerTaskConfiguration $taskConfiguration = null)
	{
		if(!$taskConfiguration instanceof IndexerTaskConfiguration){
			$taskConfiguration = GeneralUtility::makeInstance(IndexerTaskConfiguration::class);
		}
		$this->setTaskConfiguration($taskConfiguration);
		parent::__construct();
	}

	/**
	 * Runs the indexer incl. entry in table sys_registry
	 * @return bool
	 * @throws ExtensionConfigurationExtensionNotConfiguredException
	 * @throws ExtensionConfigurationPathDoesNotExistException
	 * @throws RuntimeException
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function execute(): bool
	{

		// Typoscript configuration of ke_search
		$extensionConfigKeSearch = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('ke_search');

		// Transfer the taskConfiguration from the scheduler task to the indexer runner, so we have the values there
		$indexerRunner = GeneralUtility::makeInstance(IndexerRunner::class, $this->getTaskConfiguration());

		// Remove the default ke_search registry entries (table 'sys_registry')
		$indexerRunner->registry->removeAllByNamespace('tx_kesearch');

		// Write into sys_registry
		// use $registryKey tx_kesearch_<taskUid>
		// this is a helper to delete all records which are older than starting timestamp in registry
		// this also prevents starting the indexer twice
		$nameSpace = 'allplan_ke_search_extended';
		$registryKey = 'startingTStamp_of_indexerUid:' . $this->taskUid;
		$this->setRegistryLockRecord($indexerRunner, $nameSpace, $registryKey);

		// Start the indexer
		$indexerRunner->startIndexing(true, $extensionConfigKeSearch,'CLI');

		// Remove the locking entry from sys_registry again
		$indexerRunner->registry->remove($nameSpace, $registryKey);

		return true;

	}

	/**
	 * Writes a lock into sys_registry
	 * @param IndexerRunner $indexerRunner
	 * @param string $nameSpace
	 * @param string $registryKey
	 * @throws RuntimeException
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	private function setRegistryLockRecord(IndexerRunner $indexerRunner, string $nameSpace, string $registryKey)
	{

		// If there is no entry in sys_registry => create one
		if($indexerRunner->registry->get($nameSpace, $registryKey ) === null){
			$indexerRunner->registry->set($nameSpace, $registryKey, time());
			return;
		}

		// From here: entry already exists in sys_registry

		// If lock is older than 2 hours (or on dev environment) - remove the entry and set it new, else throw Exception
		$lockTime = $indexerRunner->registry->get($nameSpace, $registryKey);
		$compareTime = time() - (60 * 60 * 2);

		if ($lockTime < $compareTime || EnvironmentUtility::isDevEnvironment()){

			$indexerRunner->registry->remove($nameSpace, $registryKey);
			$indexerRunner->registry->set($nameSpace, $registryKey, time());

		} else {
			throw new RuntimeException(
				'You cannot start the indexer twice. Please wait until the first indexer process ' . $nameSpace  . ' -> ' . $registryKey . ' is ready. '
				. 'Indexer is locked for 2 hours. Lock time start:' . date('d.m.Y H:i:s', $lockTime)
				. ' - ENV: TYPO3_CONTEXT :' .  $_ENV['TYPO3_CONTEXT'] . ' - server: ' . $_SERVER['SERVER_NAME'],
				1493994395218
			);
		}

	}

}