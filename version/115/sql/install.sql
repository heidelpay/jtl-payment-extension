/* Make sure the mail templates are filled even after updating from an older version */

/* If installed templates allready exists. Therefore they get cleaned up first use modulId to identify templates from heidelpay plugin*/
DELETE `tpluginemailvorlagesprache`
FROM `tpluginemailvorlagesprache`
       LEFT JOIN tpluginemailvorlage ON tpluginemailvorlagesprache.kEmailvorlage = tpluginemailvorlage.`kEmailvorlage`
WHERE tpluginemailvorlage.cModulId LIKE 'hp\-%\-reminder';

/* Template translations get refilled. Use default values from tpluginemailvorlagespracheoriginal.*/
INSERT INTO `tpluginemailvorlagesprache`
SELECT origin.kEmailvorlage, origin.kSprache, origin.cBetreff, origin.cContentHtml, origin.cContentText, origin.cPDFS, `origin`.`cDateiname`
FROM `tpluginemailvorlagespracheoriginal` AS `origin`
            LEFT JOIN tpluginemailvorlage ON origin.kEmailvorlage = tpluginemailvorlage.`kEmailvorlage`
WHERE tpluginemailvorlage.cModulId LIKE 'hp\-%\-reminder';

