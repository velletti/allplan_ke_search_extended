<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;


class KeSearchRegisterAdditionalFieldsHook {

    /**
     * Modify search input => sets default to AND-search (+)
     * (You can select these ones in the backend by creating a new record "indexer-configuration")
     * @param array $searchWordInformation
     * @param mixed $pObj
     */
    public function registerAdditionalFields(  &$additionalFields) {
        $additionalFields[] = 'servername' ;
    }


}