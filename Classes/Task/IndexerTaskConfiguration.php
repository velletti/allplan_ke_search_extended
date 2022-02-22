<?php
namespace Allplan\AllplanKeSearchExtended\Task;

/**
 * The settings for the IndexerTask
 * Outsourced in an own class, because settings can be transferred from IndexerTask to IndexerRunner more easily
 * @author Peter Benke <pbenke@allplan.com>
 */
class IndexerTaskConfiguration
{

	/**
	 * Class variables (additional scheduler fields)
	 * =================================================================================================================
	 */

	/**
	 * Delete entries older than number of days
	 * @var int|string|null
	 */
	protected $deleteOldEntriesPeriodInDays;

	/**
	 * Number of records, which should be indexed on one run
	 * @var int|string|null
	 */
	protected $nrOfIndexRecordsOnOneRun;

	/**
	 * Indexer configuration uid (column: tx_kesearch_indexerconfig.uid)
	 * @var int|string
	 */
	protected $indexerConfigUid;

	/**
	 * sys_language_uid, which should be written into column tx_kesearch_index.language
	 * Default null (sys_language_uid from the indexed data), maybe we want to set it explicit in backend
	 * @var int|string|null
	 */
	protected $sysLanguageUid = null;

	/**
	 * External url for indexing and list in the frontend, e.g. for the Allplan Online Help
	 * @var string|null
	 */
	protected ?string $externUrl;

	/**
	 * Explicit set pid for the index entry
	 * Default: null (then the storage pid of the indexer configuration will be set (column tx_kesearch_indexerconfig.pid))
	 * @var int|string|null
	 */
	protected $storagePid = null;


	/**
	 * Getters and setters
	 * =================================================================================================================
	 */

	/**
	 * @return int|string|null
	 */
	public function getDeleteOldEntriesPeriodInDays()
	{
		return $this->deleteOldEntriesPeriodInDays;
	}

	/**
	 * @param int|string|null $deleteOldEntriesPeriodInDays
	 */
	public function setDeleteOldEntriesPeriodInDays($deleteOldEntriesPeriodInDays): void
	{
		$this->deleteOldEntriesPeriodInDays = $deleteOldEntriesPeriodInDays;
	}

	/**
	 * @return int|string|null
	 */
	public function getNrOfIndexRecordsOnOneRun()
	{
		return $this->nrOfIndexRecordsOnOneRun;
	}

	/**
	 * @param int|string|null $nrOfIndexRecordsOnOneRun
	 */
	public function setNrOfIndexRecordsOnOneRun($nrOfIndexRecordsOnOneRun): void
	{
		$this->nrOfIndexRecordsOnOneRun = $nrOfIndexRecordsOnOneRun;
	}

	/**
	 * @return int|string
	 */
	public function getIndexerConfigUid()
	{
		return $this->indexerConfigUid;
	}

	/**
	 * @param int|string $indexerConfigUid
	 */
	public function setIndexerConfigUid($indexerConfigUid): void
	{
		$this->indexerConfigUid = $indexerConfigUid;
	}

	/**
	 * @return int|string|null
	 */
	public function getSysLanguageUid()
	{
		return $this->sysLanguageUid;
	}

	/**
	 * @param int|string|null $sysLanguageUid
	 */
	public function setSysLanguageUid($sysLanguageUid): void
	{
		$this->sysLanguageUid = $sysLanguageUid;
	}

	/**
	 * @return string|null
	 */
	public function getExternUrl(): ?string
	{
		return $this->externUrl;
	}

	/**
	 * @param string|null $externUrl
	 */
	public function setExternUrl(?string $externUrl): void
	{
		$this->externUrl = $externUrl;
	}

	/**
	 * @return int|string|null
	 */
	public function getStoragePid()
	{
		return $this->storagePid;
	}

	/**
	 * @param int|string|null $storagePid
	 */
	public function setStoragePid($storagePid): void
	{
		$this->storagePid = $storagePid;
	}

}