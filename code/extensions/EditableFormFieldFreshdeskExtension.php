<?php

class EditableFormFieldFreshdeskExtension extends DataExtension
{
    private static $db = [
        "FreshdeskFieldMapping" => "Text",
        "FreshdeskFieldCustom" => "Boolean",
    ];

    public function updateCMSFields(FieldList $fields) {
        $fields->addFieldToTab('Root.Main', new TextField('FreshdeskFieldMapping', 'Freshdesk field mapping:'));        
        $fields->addFieldToTab('Root.Main', new CheckboxField('FreshdeskFieldCustom', 'Freshdesk custom field'));
    }

    /*
    * Ensure Freshdesk fields exist via API call
    */
    public function validate(ValidationResult $validationResult) {
        $vaildFieldNames = false;

        if ($this->owner->FreshdeskFieldMapping) {
            $headers = ["Content-type" => "application/json"];
            $freshdesk = \FreshdeskAPI::create();
            $result = $freshdesk->APICall('GET', FRESHDESK_API_BASEURL, '/api/v2/ticket_fields', $headers);

            if ($result && $result->getStatusCode() == '200') {
                $validFields = json_decode($result->getBody()->getContents(), true);
            }
            
            foreach ($validFields as $field) {
                if ($field['name'] == $this->owner->FreshdeskFieldMapping) {
                    return $validationResult->valid();
                }
            }
            return $validationResult->error($this->owner->FreshdeskFieldMapping . ' is not a valid Freshdesk field');
        }
    }
}
