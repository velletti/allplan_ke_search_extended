<?php
namespace Allplan\AllplanKeSearchExtended\Indexer;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\IndexerBase as KeSearchIndexerBase;
use Tpwd\KeSearch\Indexer\IndexerRunner as KeSearchIndexerRunner;

/**
 * Base class for all Allplan indexers
 */
class IndexerBase extends KeSearchIndexerBase
{

	/**
	 * @param IndexerRunner|KeSearchIndexerRunner $indexerRunner
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function __construct($indexerRunner)
	{
		parent::__construct($indexerRunner);
		// Set the indexerRunner (defined in parent class in ke_search, variable name is not-quite-correct)
		$this->pObj = $indexerRunner;
	}

}