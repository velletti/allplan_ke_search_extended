<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MailUtility{

	/**
	 * Sends an email for debugging
	 * @param array $mailTo
	 * @param string $subject
	 * @param string $message
	 * @param string $mailFrom
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function debugMail(array $mailTo, string $subject, string $message, string $mailFrom = 'connect@systems.allplan.com')
	{

		/**
		 * @var $mail MailMessage
		 */
		$from = [
			$mailFrom => 'Debugmail'
		];

		// Check mails
		$to = [];

		foreach($mailTo as $mail){
			if(filter_var($mail, FILTER_VALIDATE_EMAIL) !== FALSE){
				$to[] = $mail;
			}
		}

		if(empty($to)){
			return;
		}

		// Append execution time
		$executionTime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
		$message.= "\n\nExecution time: " . FormatUtility::formatSeconds($executionTime);

		$mail = GeneralUtility::makeInstance(MailMessage::class);

		$mail->setSubject($subject);
		$mail->setFrom($from);
		$mail->setTo($to);
        $mail->html($message , 'UTF-8');
		// $mail->setMaxLineLength(1000);

		$mail->send();

	}

}