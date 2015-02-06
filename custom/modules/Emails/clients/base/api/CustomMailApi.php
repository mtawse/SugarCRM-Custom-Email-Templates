<?php
/**
 * @author Martin Tawse martin.tawse@gmail.com
 * Date: 06/02/2015
 *
 * Custom extesion of MailApi
 */

require_once 'modules/Emails/clients/base/api/MailApi.php';
require_once 'custom/modules/Emails/CustomMailRecord.php';

class CustomMailApi extends MailApi
{
    /**
     * Instantiate and initialize the MaiRecord from the incoming api arguments
     *
     * Override original so we can call our CustomMailRecord class
     *
     * @param $args
     * @return CustomMailRecord|MailRecord
     */
    protected function initMailRecord($args)
    {
        // TAWSE
        //$mailRecord = new MailRecord();
        $mailRecord = new CustomMailRecord();  // call custom class
        // TAWSE
        $mailRecord->mailConfig = $args[self::EMAIL_CONFIG];
        $mailRecord->toAddresses = $args[self::TO_ADDRESSES];
        $mailRecord->ccAddresses = $args[self::CC_ADDRESSES];
        $mailRecord->bccAddresses = $args[self::BCC_ADDRESSES];
        $mailRecord->attachments = $args[self::ATTACHMENTS];
        $mailRecord->teams = $args[self::TEAMS];
        $mailRecord->related = $args[self::RELATED];
        $mailRecord->subject = $args[self::SUBJECT];
        $mailRecord->html_body = $args[self::HTML_BODY];
        $mailRecord->text_body = $args[self::TEXT_BODY];
        $mailRecord->fromAddress = $args[self::FROM_ADDRESS];
        $mailRecord->assigned_user_id = $args[self::ASSIGNED_USER_ID];

        if (!empty($args[self::DATE_SENT])) {
            $date = TimeDate::getInstance()->fromIso($args[self::DATE_SENT]);
            $mailRecord->date_sent = $date->asDb();
        }

        return $mailRecord;
    }
} 