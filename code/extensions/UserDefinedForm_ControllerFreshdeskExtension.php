<?php

Class UserDefinedForm_ControllerFreshdeskExtension extends Extension
{
    public function updateEmailData($emailData, $attachments)
    {
        if (!$this->owner->FreshdeskDomain || !$this->owner->ExportToFreshdesk) {
            return false;
        }

        $formattedData = '';
        foreach ($emailData['Fields'] as $field) {
            $formattedData .= "<p><b>".$field->Title.":</b></p>";
            $formattedData .= "<p>".$field->Value."</p>";
            $formattedData .= "<br>";
        }

        $ticketData = [
          "description" => $formattedData,
          "subject" => "[".$this->owner->Title."]",
          "email" => $emailData['Sender']->Email,
          "priority" => 2,
          "status" => 2,
        ];

        if (defined("FRESHDESK_PRODUCT_ID")) {
            $ticketData['product_id'] = FRESHDESK_PRODUCT_ID;
        }

        $headers = ["Content-type" => "application/json"];
        $freshdesk = FreshdeskAPI::create();
        $freshdesk->APICall('POST', $this->owner->FreshdeskDomain, '/api/v2/tickets', $headers, $ticketData);
    }
}
