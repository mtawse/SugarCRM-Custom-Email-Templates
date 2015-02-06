<?php
/**
 * @author Martin Tawse martin.tawse@gmail.com
 * Date: 06/02/2015
 *
 * Custom extension of MailRecord
 */

require_once 'modules/Emails/MailRecord.php';
require_once 'custom/modules/Emails/CustomEmail.php';

class CustomMailRecord extends MailRecord
{
    /**
     * Prepares and executes the email request according to the expectations of the status.
     *
     * TAWSE - Overide original so we can call our CustomEmail class
     *
     * @param $status
     * @return array - Mail API Response Record
     * @throws MailerException
     */
    protected function toEmailBean($status)
    {
        if (!empty($this->mockEmailBean)) {
            $email = $this->mockEmailBean; // Testing purposes only
        } else {
            // TAWSE
            //$email = new Email();
            $email = new CustomEmail(); // custom class
            // TAWSE
        }
        $email->email2init();

        $fromAccount = null;

        if (!empty($this->mailConfig)) {
            $fromAccount = $this->mailConfig;
        }

        $to = $this->addRecipients($this->toAddresses);
        $cc = $this->addRecipients($this->ccAddresses);
        $bcc = $this->addRecipients($this->bccAddresses);

        $attachments = $this->splitAttachments($this->attachments);

        $request = $this->setupSendRequest($status, $fromAccount, $to, $cc, $bcc, $attachments);
        $_REQUEST = array_merge($_REQUEST, $request);

        $errorData = null;

        try {
            $this->startCapturingOutput();
            $email->email2Send($request);
            $errorData = $this->endCapturingOutput();

            if (strlen($errorData) > 0) {
                throw new MailerException('Email2Send returning unexpected output: ' . $errorData);
            }

            $response = $this->toApiResponse($status, $email);
            return $response;

        } catch (Exception $e) {
            if (is_null($errorData)) {
                $errorData = $this->endCapturingOutput();
            }
            if (!($e instanceof MailerException)) {
                $e = new MailerException($e->getMessage());
            }
            if (empty($errorData)) {
                $GLOBALS["log"]->error("Message: " . $e->getLogMessage());
            } else {
                $GLOBALS["log"]->error("Message: " . $e->getLogMessage() . "  Data: " . $errorData);
            }

            throw $e;
        }
    }
} 