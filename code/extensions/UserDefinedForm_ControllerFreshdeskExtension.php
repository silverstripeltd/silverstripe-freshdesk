<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

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

        $headers = ["Content-type" => "application/json"];

        $client = new Client([
            'auth' => [FRESHDESK_API_TOKEN, FRESHDESK_PASSWORD],
        ]);

        $request = new Request('POST', 'https://'.$this->owner->FreshdeskDomain.'/api/v2/tickets', $headers);

        try {
            $response = $client->send($request, ['json' => $ticketData]);
        } catch (RequestException $e) {
            echo Psr7\str($e->getRequest());
            if ($e->hasResponse()) {
                SS_Log::log(Psr7\str($e->getResponse()), SS_Log::ERR);
            }
        }
    }
}
