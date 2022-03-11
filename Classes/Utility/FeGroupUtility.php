<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

class FeGroupUtility
{

	/**
	 * => For marit elearning videos
	 * Get the fe_group, which should be written into tx_kesearch_index by a given comma separated list of fe_groups
	 * Only internal videos or "student"-videos will get a fe_group, all others return an empty string
	 * => "student"-videos should not be shown to Forum users, SP users, etc...
	 * @param string $feGroupList
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getElearningFeGroupForIndex(string $feGroupList):string
	{

		$feGroups = explode(',', $feGroupList);

		// If one of these groups is set in a record of marit elearning videos (tx_maritelearning_domain_model_lesson)
		// => No group will be set for ke_search index, otherwise the fe_group will be the same as before
		$ignoreFeGroups = [
			'1',  // Forum user
			'3',  // SP user
			'8',  // Anonymous
			'10', // Interested user Connect
			'11', // Customer Connect
		];

		foreach($ignoreFeGroups as $ignoreFeGroup){
			if(in_array($ignoreFeGroup, $feGroups)){
				$feGroupList = '';
			}
		}

		return $feGroupList;

	}

}