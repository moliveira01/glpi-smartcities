ALTER TABLE `glpi_plugin_environment_profiles` 
   CHANGE `ID` `id` int(11) NOT NULL auto_increment,
   ADD `profiles_id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_profiles (id)',
   CHANGE `environment` `environment` char(1) collate utf8_unicode_ci default NULL,
   CHANGE `applicatifs` `appliances` char(1) collate utf8_unicode_ci default NULL,
   CHANGE `appweb` `webapplications` char(1) collate utf8_unicode_ci default NULL,
   CHANGE `certificates` `certificates` char(1) collate utf8_unicode_ci default NULL,
   CHANGE `compte` `accounts` char(1) collate utf8_unicode_ci default NULL,
   DROP `connections`,
   CHANGE `domain` `domains` char(1) collate utf8_unicode_ci default NULL,
   CHANGE `sgbd` `databases` char(1) collate utf8_unicode_ci default NULL,
   DROP `backups`,
   DROP `parametre`,
   CHANGE `badges` `badges` char(1) collate utf8_unicode_ci default NULL,
   DROP `droits`,
   ADD INDEX (`profiles_id`);