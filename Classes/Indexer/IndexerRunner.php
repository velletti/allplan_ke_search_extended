<?php
namespace Allplan\AllplanKeSearchExtended\Indexer;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Task\IndexerTaskConfiguration;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\IndexerBase;
use Tpwd\KeSearch\Indexer\IndexerRunner as KeSearchIndexerRunner;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IndexerRunner extends KeSearchIndexerRunner
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
	 * @param IndexerTaskConfiguration $taskConfiguration
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function __construct(IndexerTaskConfiguration $taskConfiguration)
	{
		$this->setTaskConfiguration($taskConfiguration);
		parent::__construct();
	}

	/**
	 * Extends the parent function startIndexing()
	 * @param bool $verbose
	 * @param array $extConf
	 * @param string $mode
	 * @param int $indexingMode
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function startIndexing($verbose = true, $extConf = array(), $mode = '', $indexingMode = IndexerBase::INDEXING_MODE_FULL): string
	{
		// Put code here before starting indexing...

		// Parent function (there the CustomIndexerHook will be called)
		return parent::startIndexing($verbose, $extConf, $mode, $indexingMode);
	}

}