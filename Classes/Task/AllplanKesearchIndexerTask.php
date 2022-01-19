<?php
namespace Allplan\AllplanKeSearchExtended\Task;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\AllplanKesearchIndexer;

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

class AllplanKesearchIndexerTask extends AbstractTask
{

	/**
	 * @var int The time period, after which the rows are deleted
	 */
	protected int $period;

	/**
	 * @var int language
	 */
	protected int $language;

	/**
	 * @var int rowcount
	 */
	protected int $rowcount;

	/**
	 * @var string externalUrl
	 */
	protected string $externalUrl;

	/**
	 * @var int storagePid
	 */
	protected int $storagePid;

	/**
	 * @var array The index Configs records that should be used for scheduler index
	 */
	public array $configs;


	/**
	 * @return array
	 */
	public function getConfigs(): array
	{
		return $this->configs;
	}

	/**
	 * @param array $configs
	 */
	public function setConfigs(array $configs)
	{
		$this->configs = $configs;
	}

	/**
	 * @return int
	 */
	public function getPeriod(): int
	{
		return $this->period;
	}

	/**
	 * @param int $period
	 */
	public function setPeriod(int $period)
	{
		$this->period = $period;
	}

	/**
	 * @return int
	 */
	public function getLanguage(): int
	{
		return $this->language;
	}

	/**
	 * @param int $language
	 */
	public function setLanguage(int $language)
	{
		$this->language = $language;
	}

	/**
	 * @return string
	 */
	public function getExternalUrl(): string
	{
		return $this->externalUrl;
	}

	/**
	 * @param string $externalUrl
	 */
	public function setExternalUrl(string $externalUrl)
	{
		$this->externalUrl = $externalUrl;
	}

	/**
	 * @return int
	 */
	public function getRowcount(): int
	{
		return $this->rowcount;
	}

	/**
	 * @param int $rowcount
	 */
	public function setRowcount(int $rowcount)
	{
		$this->rowcount = $rowcount;
	}

	/**
	 * @return int
	 */
	public function getStoragePid(): int
	{
		return $this->storagePid;
	}

	/**
	 * @param int $storagePid
	 */
	public function setStoragePid(int $storagePid)
	{
		$this->storagePid = $storagePid;
	}

	/**
	 * @return bool
	 * @throws ExtensionConfigurationExtensionNotConfiguredException
	 * @throws ExtensionConfigurationPathDoesNotExistException
	 */
	public function execute(): bool
	{

		$this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('ke_search');
		$indexer = GeneralUtility::makeInstance(AllplanKesearchIndexer::class);
		$indexer->configs = $this->configs;
		$indexer->period = $this->period;
		$indexer->language = $this->language;
		$indexer->externalUrl = $this->externalUrl;
		$indexer->rowcount = $this->rowcount;
		$indexer->storagePid = $this->storagePid;

		// At first remove the default ke_search registry entries (needed, because default index will set it again)
		$indexer->registry->removeAllByNamespace('tx_kesearch');

		// Now write the starting timestamp into registry, but use $nameSpace tx_kesearch_<taskUid>
		// this is a helper to delete all records which are older than starting timestamp in registry
		// this also prevents starting the indexer twice
		// Todo Check this...
		$nameSpace = 'tx_kesearch_extended';
		$registryKey = 'startTimeOfIndexer' . $this->taskUid;

		if($indexer->registry->get($nameSpace, $registryKey ) === null){
			$indexer->registry->set($nameSpace, $registryKey, time());
		} else {

			// check lock time
			$lockTime = $indexer->registry->get($nameSpace, $registryKey);
			$compareTime = time() - (60*60*12);

			// Todo put this into function
			// If lock is older than 12 hours (or on dev environment) - remove
			if ($lockTime < $compareTime || substr($_SERVER['SERVER_NAME'] , -9 , 9 ) == 'ddev.site' || $_ENV['TYPO3_CONTEXT'] == 'Development'){
				$indexer->registry->remove($nameSpace , $registryKey );
				$indexer->registry->set($nameSpace, $registryKey , time());
			} else {
				throw new RuntimeException(
					'You cannot start the indexer twice. Please wait while the first indexer process ' . $nameSpace  . ' -> ' . $registryKey
					. ' is currently running: lock time:' . date('d.m.Y H:i:s', $lockTime) . ' > ' . date('d.m.Y H:i:s', $compareTime)
					. ' - ENV: TYPO3_CONTEXT :' .  $_ENV['TYPO3_CONTEXT'] . ' - server: ' . $_SERVER['SERVER_NAME'],
					1493994395218
				);
			}
		}

		// Process
		$indexer->startIndexing(true, $this->extConf,'CLI');
		$indexer->registry->remove($nameSpace, $registryKey);
		return true;

	}

}