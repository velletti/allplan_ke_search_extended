# allplan_ke_search_extended

This extension extends the extension ke_search and is used on www.allplan.com as well as on connect.allplan.com.
Everything is stored in storage folders considering the same tree structure with the same storage folder uids on www and on connect.

## How to create a new indexer

1. Register a new indexer configuration in **RegisterIndexerConfigurationHook**
2. Create a new indexer on sys_folder [5080]/[5081] (www/connect). Create a sys_folder<sup>1</sup> for the index entries.
3. Add a new entry to **CustomIndexerHook**
4. Create a new Indexer inside Classes/Indexer/. Extend from **IndexerBase** and implement **IndexerInterface**
5. Add some cleanup in **CleanupHook**, if needed

**Footnotes**:\
<sup>1</sup>sys_folder for the index entries:\
It is good practice splitting the several entries into several sys_folders because of cross-country-languages.
E.g.: Allplan Online Help DE => Is shown on Germany, Austria and Switzerland => So we can build queries in a simple way.


## Working principle of this extension

1. Scheduler task is created
2. On start the scheduler task (IndexerTask) transfers its settings to the IndexerRunner-properties (which extends the ke_search indexer runner) on start.
3. After that, the IndexerTask starts the IndexerRunner->startIndexing(), which starts the parent startIndexing() function.
4. There ke_search has a hook implemented, which calls our CustomIndexerHook->customIndexer() with the IndexRunner (and its properties)
5. So we have a CustomIndexer with the originally settings from the scheduler task :-)
6. Indexer starts in our own IndexerTask => there the parent startIndexing() function is called.

### Notes

1. On start indexing, ke_search writes an entry to sys_registry with current timestamp
2. Index records will be updated (instead of deleted and new created) or new ones will be inserted => in function prepareStatements()
3. ke_search now deletes all entries (**after** update records), which are older than the starting timestamp. This is done in its cleanUpIndex()-function, which is hooked by our cleanUp()-function.

**This means**:
=> If an indexer get the new records for indexing (e.g. from forum topics), a deleted entry will not exist in the result list no longer
=> so it will not be updated in index
=> timestamp will not change
=> will be deleted by ke_search
=> everything is fine :-)

From the "official" documentation:
"Full indexing goes through all the data which should be indexed (records, files, custom indexers) and checks for each record if it has been changed or if it is new. In this case the data will be updated or stored to the index. **After that** a cleanup process is started and all old data will be deleted."

## Tags

The tags are set as follows:
1. Inside the Indexer->storeInKeSearchIndex() function (hardcoded)
2. Set in backend inside the indexer by selection of the filter (which has set a tag)
=> So from these places a comma seperated list of tags is written into tx_kesearch_index.tags

## Test task on LOCAL cli

### Before every start => DB:

    UPDATE tx_scheduler_task SET nextexecution='1642431600', lastexecution_failure='', serialized_executions='' WHERE uid=[scheduler_task_uid];
    TRUNCATE Table sys_registry;
    TRUNCATE Table tx_kesearch_index;

### Cli

    /var/www/html/http/typo3/sysext/core/bin/typo3 scheduler:run --task=[scheduler_task_uid] -vv

