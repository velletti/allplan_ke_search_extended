<?php
namespace Allplan\AllplanKeSearchExtended\Indexer;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\IndexerRunner as KeSearchIndexerRunner;

/**
 * Provides the needed functions for the Allplan indexers
 */
interface IndexerInterface
{

	/**
	 * Indexer function
	 * @return int
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function startIndexing(): int;

	/**
	 * Write data to index (tx_kesearch_index)
	 * @param array $record
	 * @param IndexerRunner|KeSearchIndexerRunner $indexerRunner
	 * @param array $indexerConfig
	 * @return bool|int
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function storeInKeSearchIndex(array $record, IndexerRunner $indexerRunner, array $indexerConfig);

}