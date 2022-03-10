<?php
namespace Allplan\AllplanKeSearchExtended\Indexer\Connect;

/**
 * Class just to provide the various forum indexer properties
 * (because we can easily initialize this class without constructor arguments as in the indexer itself)
 */
class MmForumIndexerProperties
{

	/**
	 * Forum indexer types
	 */
	const FORUM_INDEXER_TYPE_DEFAULT = 'mm_forum';
	const FORUM_INDEXER_TYPE_SP = 'mm_forum_sp';
	const FORUM_INDEXER_TYPE_LOCKED = 'mm_forum_locked';

	/**
	 * Forum indexer storage pids
	 */
	const FORUM_INDEXER_STORAGE_PID_EN = 5004;
	const FORUM_INDEXER_STORAGE_PID_DACH = 5003;
	const FORUM_INDEXER_STORAGE_PID_OTHERS = 5005;

}