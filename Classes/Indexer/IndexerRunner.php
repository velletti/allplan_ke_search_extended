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
	protected IndexerTaskConfiguration $configuration;

	/**
	 * @return IndexerTaskConfiguration
	 */
	public function getConfiguration(): IndexerTaskConfiguration
	{
		return $this->configuration;
	}

	/**
	 * @param IndexerTaskConfiguration $configuration
	 */
	public function setConfiguration(IndexerTaskConfiguration $configuration): void
	{
		$this->configuration = $configuration;
	}

	/**
	 * Functions
	 * =================================================================================================================
	 */

	/**
	 * Initialize the task configuration object and call the parent constructor
	 * @param IndexerTaskConfiguration $configuration
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function __construct(IndexerTaskConfiguration $configuration)
	{
		$this->setConfiguration($configuration);
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