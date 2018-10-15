<?php

class UserDefinedForm_ControllerFreshdeskExtension extends Extension
{
    /**
     * @var FreshdeskService
     */
    public $freshdeskService;

    private static $dependencies = [
        'freshdeskService' => '%$FreshdeskService',
    ];

    public function updateEmailData($emailData, $attachments)
    {
        if (!$this->owner->ExportToFreshdesk) {
            return false;
        }

        if (!$emailData['Sender'] instanceof Member) {
            SS_Log::log('User must be logged in to raise Freshdesk tickets', SS_Log::ERR);

            return false;
        }

        $productID = null;
        if (defined('FRESHDESK_PRODUCT_ID')) {
            $productID = FRESHDESK_PRODUCT_ID;
        }

        $ticketData = [
            'subject' => $this->owner->Title,
            'email' => $emailData['Sender']->Email,
            'priority' => 2,
            'status' => 2,
            'product_id' => $productID,
            'description' => '',
        ];

        foreach ($emailData['Fields'] as $field) {
            $editableFormField = $this->owner->Fields()->find('Name', $field->Name);
            $mappingField = $editableFormField->FreshdeskFieldMapping;
            $isCustomField = $editableFormField->FreshdeskFieldCustom;
            $forceInteger = $editableFormField->FreshdeskForceInt;

            if ($mappingField) {
                if ($forceInteger) {
                    $field->Value = (int) $field->Value;
                }
                if ($isCustomField) {
                    $ticketData['custom_fields'][$mappingField] = $field->Value;
                    continue;
                }
                $ticketData[$mappingField] = $field->Value;
                continue;
            }

            if (!$field->Value) {
                continue;
            }

            $ticketData['description'] .= '<p><b>'.$field->Title.':</b></p>';
            $ticketData['description'] .= '<p>'.$field->Value.'</p>';
            $ticketData['description'] .= '<br>';
        }

        $this->freshdeskService->makeTicket($ticketData);
    }
}
