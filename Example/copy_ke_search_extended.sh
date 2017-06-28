#!/bin/bash

# ==================================================================================================
# allplan_ke_search_extended
# ==================================================================================================

# Rsync www.allplan.com
# --------------------------------------------------------------------------------------------------
# Only dry run
# rsync -av --dry-run --delete --exclude='.git*' ./allplan_ke_search_extended/ ../www.allplan.com/http/typo3conf/ext/allplan_ke_search_extended/
rsync -av --delete --exclude='.git*' ./allplan_ke_search_extended/ ../www.allplan.com/http/typo3conf/ext/allplan_ke_search_extended/

# Rsync connect.allplan.com
# --------------------------------------------------------------------------------------------------
# Only dry run
# rsync -av --dry-run --delete --exclude='.git*' ./allplan_ke_search_extended/ ../connect.allplan.com/http/typo3conf/ext/allplan_ke_search_extended/
rsync -av --delete --exclude='.git*' ./allplan_ke_search_extended/ ../connect.allplan.com/http/typo3conf/ext/allplan_ke_search_extended/
