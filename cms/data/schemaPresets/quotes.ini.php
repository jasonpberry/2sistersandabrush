<?php /* This is a PHP data file */ if (!@$LOADSTRUCT) { die("This is not a program file."); }
return array (
  '_detailPage' => '',
  '_disableAdd' => '0',
  '_disableErase' => '0',
  '_disableView' => '1',
  '_filenameFields' => '',
  '_hideRecordsFromDisabledAccounts' => '0',
  '_listPage' => '',
  '_maxRecords' => '',
  '_maxRecordsPerUser' => '',
  '_tableName' => 'quotes',
  'listPageFields' => 'dragSortOrder, name',
  'listPageOrder' => 'dragSortOrder DESC',
  'listPageSearchFields' => '_all_',
  'menuName' => 'Quotes',
  'menuOrder' => '1211907001',
  'menuType' => 'multi',
  'num' => array(
    'order' => '1',
    'type' => 'none',
    'label' => 'Record Number',
    'isSystemField' => '1',
  ),
  'createdDate' => array(
    'order' => '2',
    'type' => 'none',
    'label' => 'Created',
    'isSystemField' => '1',
  ),
  'createdByUserNum' => array(
    'order' => '3',
    'type' => 'none',
    'label' => 'Created By',
    'isSystemField' => '1',
  ),
  'updatedDate' => array(
    'order' => '4',
    'type' => 'none',
    'label' => 'Last Updated',
    'isSystemField' => '1',
  ),
  'updatedByUserNum' => array(
    'order' => '5',
    'type' => 'none',
    'label' => 'Last Updated By',
    'isSystemField' => '1',
  ),
  'dragSortOrder' => array(
    'order' => '6',
    'label' => 'Order',
    'type' => 'none',
  ),
  'name' => array(
    'order' => '7',
    'label' => 'Name',
    'type' => 'textfield',
    'isSystemField' => '0',
    'defaultValue' => '',
    'isPasswordField' => '0',
    'isRequired' => '1',
    'isUnique' => '0',
    'minLength' => '',
    'maxLength' => '0',
    'charsetRule' => '',
    'charset' => '',
  ),
  'company' => array(
    'order' => '8',
    'label' => 'Company',
    'type' => 'textfield',
    'isSystemField' => '0',
    'defaultValue' => '',
    'isPasswordField' => '0',
    'isRequired' => '0',
    'isUnique' => '0',
    'minLength' => '',
    'maxLength' => '',
    'charsetRule' => '',
    'charset' => '',
  ),
  'pullQuote' => array(
    'order' => '9',
    'label' => 'Pull Quote (small \'sound bite\' quote)',
    'type' => 'textbox',
    'isSystemField' => '0',
    'defaultContent' => '',
    'isRequired' => '0',
    'isUnique' => '0',
    'minLength' => '',
    'maxLength' => '',
    'fieldHeight' => '45',
    'autoFormat' => '1',
  ),
  'quote' => array(
    'order' => '10',
    'label' => 'Quote',
    'type' => 'textbox',
    'defaultContent' => '',
    'description' => '',
    'isRequired' => '0',
    'isUnique' => '0',
    'minLength' => '',
    'maxLength' => '',
    'fieldHeight' => '140',
    'autoFormat' => '1',
  ),
  'website' => array(
    'order' => '11',
    'label' => 'Website',
    'type' => 'textfield',
    'isSystemField' => '0',
    'defaultValue' => '',
    'isPasswordField' => '0',
    'isRequired' => '0',
    'isUnique' => '0',
    'minLength' => '',
    'maxLength' => '',
    'charsetRule' => '',
    'charset' => '',
  ),
);
?>