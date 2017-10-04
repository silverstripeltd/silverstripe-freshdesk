<?php

class UserDefinedFormFreshdeskExtension extends DataExtension
{
    private static $db = [
        "ExportToFreshdesk" => "Boolean",
        "FreshdeskDescription" => "HTMLText",
    ];

    public function updateCMSFields(FieldList $fields) {
        $fields->addFieldToTab('Root.FormOptions', new CheckboxField('ExportToFreshdesk', 'Export as a Freshdesk ticket on submit'));
        $fields->addFieldToTab('Root.FormOptions', new TextareaField('FreshdeskDescription', 'Freshdesk ticket description:'));
    }
}
