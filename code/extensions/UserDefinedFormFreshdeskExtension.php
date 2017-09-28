<?php

Class UserDefinedFormFreshdeskExtension extends DataExtension
{
    private static $db = [
        "ExportToFreshdesk" => "Boolean",
        "FreshdeskDomain" => "Varchar(255)",
    ];

    public function updateCMSFields(FieldList $fields) {
        $fields->addFieldToTab('Root.FormOptions', new CheckboxField('ExportToFreshdesk', 'Export as a Freshdesk ticket on submit'));
        $fields->addFieldToTab('Root.FormOptions', new TextField('FreshdeskDomain', 'Freshdesk portal to raise the ticket with:'));
    }
}
