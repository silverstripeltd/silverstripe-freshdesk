<?php

class UserDefinedFormFreshdeskExtension extends DataExtension
{
    private static $db = [
        'ExportToFreshdesk' => 'Boolean',
        'FreshdeskDescription' => 'HTMLText',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.FormOptions', new CheckboxField('ExportToFreshdesk', 'Export as a Freshdesk ticket on submit'));
        $fields->addFieldToTab('Root.FormOptions', new TextareaField('FreshdeskDescription', 'Freshdesk ticket description:'));
    }

    public function validate(ValidationResult $validationResult)
    {
        parent::validate($validationResult);

        if (!$this->owner->ExportToFreshdesk) {
            return $validationResult->valid();
        }

        if (!defined('FRESHDESK_API_BASEURL') || empty('FRESHDESK_API_BASEURL')) {
            return $validationResult->error('FRESHDESK_API_BASEURL must be defined');
        }
    }
}
