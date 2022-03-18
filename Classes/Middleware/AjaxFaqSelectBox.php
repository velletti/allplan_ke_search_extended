<?php
namespace Allplan\AllplanKeSearchExtended\Middleware;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\ViewHelpers\KeSearchDirectoryViewHelper;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * Psr
 */
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Gets the select box(es) for the FAQ in Connect (page uid [14081])
 */
class AjaxFaqSelectBox implements MiddlewareInterface
{

	/**
	 * @param ServerRequestInterface $request
	 * @param RequestHandlerInterface $handler
	 * @return ResponseInterface
	 */
	public function process(
		ServerRequestInterface $request,
		RequestHandlerInterface $handler
	): ResponseInterface
	{

		$queryParams = $request->getQueryParams();

		// If kesearch is called, e.g.:
		// https://connect.allplan.com/index.php?id=14081&eIDMW=kesearch&tx_kesearch_pi1[directory]=Ingenieurbau%5CStatik&tx_kesearch_pi1[level]=3&L=1&_=1647271661061
		// https://connectv10.allplan.com.ddev.site/index.php?id=14081&eIDMW=kesearch&tx_kesearch_pi1[directory]=Ingenieurbau%5CStatik&tx_kesearch_pi1[level]=3&L=1&_=1647271661061
		if(isset($queryParams['eIDMW']) && $queryParams['eIDMW'] == 'kesearch'){

			$KeSearchDirectoryViewHelper = GeneralUtility::makeInstance(KeSearchDirectoryViewHelper::class);
			try{
				$contentArray = $KeSearchDirectoryViewHelper->getCategoriesAsSelectBox();
			}catch(DoctrineDBALDriverException $e){
				$contentArray = [];
			}

			$result = '';
			if(array_key_exists('tx_kesearch_pi1', $queryParams ) && array_key_exists ('level', $queryParams['tx_kesearch_pi1'])){
				if($queryParams['tx_kesearch_pi1']['level'] == 2){
					$result = $contentArray["select2"];
				}
				if($queryParams['tx_kesearch_pi1']['level'] == 3){
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

}