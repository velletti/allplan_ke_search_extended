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
	 * Todo Add this function in documentation
	 * Clean up the index before indexing starts
	 * Call this function inside the CustomIndexerHook, if needed
	 * The cleanUpIndex()-function of ke_search (with its cleanup-hook) starts after indexing, but we need more flexibility
	 * Example: we may have forum posts, which were deleted weeks after creating => so the index entry should be removed also, if there is any
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function cleanUpBeforeIndexing();

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