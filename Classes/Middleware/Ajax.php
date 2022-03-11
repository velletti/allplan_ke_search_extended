<?php
namespace Allplan\AllplanKeSearchExtended\Middleware;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\ViewHelpers\KeSearchDirectoryViewHelper;

/**
 * Psr
 */
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;

// Todo: check, if needed, when new faq indexer ready

class Ajax implements MiddlewareInterface
{
	/**
	 * Todo Annotation
	 * @param ServerRequestInterface $request
	 * @param RequestHandlerInterface $handler
	 * @return ResponseInterface
	 */
	public function process(
		ServerRequestInterface $request,
		RequestHandlerInterface $handler
	): ResponseInterface
	{

		$_gp = $request->getQueryParams();

		/**
		 * https://connectv9.allplan.com.ddev.site/index.php?eIDMW=mm_forum&id=39&L=1&tx_mmforum_ajax%5Bcontroller%5D=Ajax&tx_mmforum_ajax%5BcallAction%5D=postSummaries&tx_mmforum_ajax%5Bformat%5D=json&tx_mmforum_ajax%5Bdata%5D%5BpostUids%5D=%5B311783%2C311781%2C311780%2C311775%2C311769%2C311766%2C311765%2C311763%2C311760%2C311757%2C311751%2C311747%2C311743%2C311738%2C311736%2C311730%2C311724%2C311722%2C311719%2C311715%5D&_=1619173455725
		 * https://connectv10.allplan.com.ddev.site/index.php?eIDMW=mm_forum&id=39&L=0&tx_mmforum_ajax%5Bcontroller%5D=User&tx_mmforum_ajax%5Baction%5D=subscribe&tx_mmforum_ajax%5Btype%5D=forum&tx_mmforum_ajax%5BobjectUid%5D=173&tx_mmforum_ajax%5Bunsubscribe%5D=1&_=1630314918549
		 */

		if(is_array($_gp) && key_exists('eIDMW', $_gp) && $_gp['eIDMW'] == 'kesearch'){

			$KeSearchDirectoryViewHelper = GeneralUtility::makeInstance(KeSearchDirectoryViewHelper::class);
			$contentArray = $KeSearchDirectoryViewHelper->getCategories();
			$result = '';

			if(array_key_exists('tx_kesearch_pi1', $_gp ) && array_key_exists ('level', $_gp['tx_kesearch_pi1'])){
				if($_gp['tx_kesearch_pi1']['level'] == 2){
					$result = $contentArray["select2"];
				}
				if($_gp['tx_kesearch_pi1']['level'] == 3){
					$result = $contentArray["select3"];
				}
			}
			$status = $result ? 200 : 404;

			$body = new Stream('php://temp','rw');
			$body->write(($result));

			return (new Response())
				->withHeader('Expires','Mon, 26 Jul 1997 05:00:00 GMT')
				->withHeader('Last-Modified',gmdate('D, d M Y H:i:s') . ' GMT')
				->withHeader('content-type','text/html; charset=utf-8')
				->withHeader('Pragma','no-cache')
				->withHeader('Content-Transfer-Encoding','8bit')
				->withHeader('Cache-Control','no-cache, must-revalidate')
				->withBody($body)
				->withStatus($status );
		}

		return $handler->handle($request);

	}

	/**
	 * Dispatches a request
	 * @param array|null $_gp
	 * @return string
	 */
	public function dispatch(?array $_gp): string
	{

		$action = $_gp['tx_mmforum_ajax']['action'] ?? 'main';
		$configuration = [
			'vendorName' => 'Allplan',
			'extensionName' => 'MmForum', // CamelCase
			'pluginName' => 'Ajax',
			'action' => $action,
			'mvc' => [
				'requestHandlers' => [
					'TYPO3\CMS\Extbase\Mvc\Web\FrontendRequestHandler' => 'TYPO3\CMS\Extbase\Mvc\Web\FrontendRequestHandler'
				]
			],
		];
		// var_dump($configuration);
		// die;

		$extbaseBootstrap = GeneralUtility::makeInstance(Bootstrap::class);
		return $extbaseBootstrap->run('', $configuration);

	}

	/**
	 * Catch invalid "tx_mmforum_ajax[action]"-parameters
	 * @param array|null $_gp
	 * @return bool
	 */
	private function callActionParameterIsValid(?array $_gp): bool
	{

		// If action is not "main", we don't have to check the call action
		if(!isset($_gp['tx_mmforum_ajax']['action']) || $_gp['tx_mmforum_ajax']['action'] = 'main'){
			return true;
		}

		// The parameter "callAction" only has to be set, if action is "main"
		if(!isset($_gp['tx_mmforum_ajax']['callAction']) && $_gp['tx_mmforum_ajax']['action'] == 'main'){
			return false;
		}

		// Parameter has to be in one of the allowed callActions
		if(isset($_gp['tx_mmforum_ajax']['callAction']) && !in_array($_gp['tx_mmforum_ajax']['callAction'], $this->getAllowedActions())){
			return false;
		}
		if(isset($_gp['tx_mmforum_ajax']['action']) && !in_array($_gp['tx_mmforum_ajax']['action'], $this->getAllowedActions() )){
			return false;
		}

		return true;

	}

	/**
	 * Catch invalid "tx_mmforum_ajax[controller]"-parameters
	 * @param array|null $_gp
	 * @return bool
	 */
	private function callControllerParameterIsValid(?array $_gp): bool
	{

		// If action is not "main", we don't have to check the call action
		if(!isset($_gp['tx_mmforum_ajax']['controller'])){
			return true;
		}

		if( $_gp['tx_mmforum_ajax']['controller'] == "Post" || $_gp['tx_mmforum_ajax']['controller'] == "Ajax" ){
			return true ;
		}

		return false;

	}

	/**
	 * Get the current action
	 * If action is not valid action 'main' is the fallback
	 * @param array $_gp
	 * @return string
	 */
	public function getAction(array $_gp): string
	{

		if(isset($_gp['tx_mmforum_ajax']['callAction'])){
			$action = trim($_gp['tx_mmforum_ajax']['callAction']);
			if(in_array($action, $this->getAllowedActions())){
				return  $action;
			}
		}

		return 'main';

	}

	/**
	 * Get the allowed actions
	 * @return string[]
	 */
	public function getAllowedActions(): array
	{

		// On changes, see also: /typo3conf/ext/mm_forum/Classes/Controller/AjaxController.php->mainAction
		return [

			'forumMenu',
			'postSummaries',
			'topicStatus',
			'topicMenu',
			'postActionBars',
			'userDetails',

			// Post Controller
			'addSupporter',
			'removeSupporter',
			'addDisliker',
			'removeDisliker',
			'preview',

			// User Controller
			'subscribe',
			'nrOfMessages',
			'favSubscribe',

			// Tag Controller
			'autoComplete',

		];
	}

}
