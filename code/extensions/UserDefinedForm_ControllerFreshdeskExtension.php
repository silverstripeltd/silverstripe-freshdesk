<?php

Class UserDefinedForm_ControllerFreshdeskExtension extends Extension
{
    public function updateEmailData($emailData, $attachments)
    {
        if (!$this->owner->ExportToFreshdesk) {
            return false;
        }

        if (!defined('FRESHDESK_API_BASEURL') || empty('FRESHDESK_API_BASEURL')) {
            SS_Log::log("Ticket is intended to be exported to Freshdesk but FRESHDESK_API_BASEURL is not defined", SS_Log::ERR);
            return false;
        }


        if (!$emailData['Sender'] instanceof Member) {
            SS_Log::log("User must be logged in to raise Freshdesk tickets", SS_Log::ERR);
            return false;
        }

        $formattedData = '';
        foreach ($emailData['Fields'] as $field) {
            $formattedData .= "<p><b>".$field->Title.":</b></p>";
            $formattedData .= "<p>".$field->Value."</p>";
            $formattedData .= "<br>";
        }

        $productID = null;
        if (defined("FRESHDESK_PRODUCT_ID")) {
            $productID = FRESHDESK_PRODUCT_ID;
        }

        $ticketData = [
          "description" => $formattedData,
          "subject" => "[".$this->owner->Title."]",
          "email" => $emailData['Sender']->Email,
          "priority" => 2,
          "status" => 2,
          "product_id" => $productID,
        ];

        $headers = ["Content-type" => "application/json"];
        $freshdesk = FreshdeskAPI::create();
        $freshdesk->APICall('POST', FRESHDESK_API_BASEURL, '/api/v2/tickets', $headers, $ticketData);
    }
}
