INSERT IGNORE INTO `#__rsform_config` (`SettingName`, `SettingValue`) VALUES
('trangellsaman.samanmerchantid', ''),
('trangellsaman.tax.value', '');

INSERT IGNORE INTO `#__rsform_component_types` (`ComponentTypeId`, `ComponentTypeName`) VALUES (203, 'trangellsaman');

DELETE FROM #__rsform_component_type_fields WHERE ComponentTypeId = 203;
INSERT IGNORE INTO `#__rsform_component_type_fields` (`ComponentTypeId`, `FieldName`, `FieldType`, `FieldValues`, `Ordering`) VALUES
(203, 'NAME', 'textbox', '', 0),
(203, 'LABEL', 'textbox', '', 1),
(203, 'COMPONENTTYPE', 'hidden', '203', 2),
(203, 'LAYOUTHIDDEN', 'hiddenparam', 'YES', 7);
