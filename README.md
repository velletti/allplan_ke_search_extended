(C) Allplan Gmbh

this is used in productive env. of our website. 
We think, that these indexers are so specific, that they cannot be used by someone else, but are a cool a "best pratice" Example.


Some special Indexer we need for the TYPO3 extension ke_search:

  - index external Pages, not made with TYPO3
    - using Sitemap.xlm and CURL and specific div classnames as Content
	- using search.json for creating the search result entries
	- mixing both technics (get sitemap.xml and indexing single pages via curl
	
  - Indexer for jv_events
  
  much more in work (forum and other allplan gmbh special extensions) 
  
  
Enhancements for ke_search using the existing hooks in ke_search: 

  - allows to run some indexer every hour and other only once a week
  - remove unwanted content from search request (also to avoid sql injections)

  

