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
	 * @var int|string|null The time period, after which the rows are deleted
	 */
	protected $period;

	/**
	 * @var string|int|null
	 */
	protected $language;

	/**
	 * @var string|int|null
	 */
	protected $rowcount;

	/**
	 * @var string
	 */
	protected string $externalUrl;

	/**
	 * @var string|int|null
	 */
	protected $storagePid;

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
	 * @return int|string|null
	 */
	public function getPeriod()
	{
		return $this->period;
	}

	/**
	 * @param int|string|null $period
	 */
	public function setPeriod($period)
	{
		$this->period = $period;
	}

	/**
	 * @return string|int|null
	 */
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * @param string|int|null $language
	 */
	public function setLanguage($language)
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
	 * @return string|int|null
	 */
	public function getRowcount()
	{
		return $this->rowcount;
	}

	/**
	 * @param string|int|null $rowcount
	 */
	public function setRowcount($rowcount)
	{
		$this->rowcount = $rowcount;
	}

	/**
	 * @return string|int|null
	 */
	public function getStoragePid()
	{
		return $this->storagePid;
	}

	/**
	 * @param string|int|null $storagePid
	 */
	public function setStoragePid($storagePid)
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