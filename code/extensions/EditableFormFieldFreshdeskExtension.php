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
}
