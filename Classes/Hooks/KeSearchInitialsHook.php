<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

use org\bovigo\vfs\vfsStreamWrapperTestCase;

class KeSearchInitialsHook {

	/**
	 * Adds some infos to the rendering FlexForm
	 * DOES NOT Work to set template Paths / PartialPaths because  the rendering engine Object is defined as protected
	 * @param mixed $pObj
	 */
	public function addInitials(&$pObj) {

        // maybe we need this in teh future
	}



}