<?php

Class UserDefinedFormFreshdeskExtension extends DataExtension
{
    private static $db = [
        "ExportToFreshdesk" => "Boolean",
        "FreshdeskDomain" => "Varchar(255)",
        "FreshdeskDescription" => "HTMLText",
    ];

    public function updateCMSFields(FieldList $fields) {
        $fields->addFieldToTab('Root.FormOptions', new CheckboxField('ExportToFreshdesk', 'Export as a Freshdesk ticket on submit'));
        $fields->addFieldToTab('Root.FormOptions', new TextField('FreshdeskDomain', 'Freshdesk API domain to raise the ticket with:'));
        $fields->addFieldToTab('Root.FormOptions', new TextareaField('FreshdeskDescription', 'Freshdesk ticket description:'));
    }

    public function populateDefaults() {
        if (defined("FRESHDESK_API_BASEURL")) {
            $this->owner->FreshdeskDomain = FRESHDESK_API_BASEURL;
        }
        parent::populateDefaults();
    }
}
