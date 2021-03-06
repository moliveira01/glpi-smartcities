<?php

/*
   ------------------------------------------------------------------------
   FusionInventory
   Copyright (C) 2010-2014 by the FusionInventory Development Team.

   http://www.fusioninventory.org/   http://forge.fusioninventory.org/
   ------------------------------------------------------------------------

   LICENSE

   This file is part of FusionInventory project.

   FusionInventory is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   FusionInventory is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with FusionInventory. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   FusionInventory
   @author    Vincent Mazzoni
   @co-author
   @copyright Copyright (c) 2010-2014 FusionInventory team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      http://www.fusioninventory.org/
   @link      http://forge.fusioninventory.org/projects/fusioninventory-for-glpi/
   @since     2010

   ------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFusioninventoryNetworkEquipment extends CommonDBTM {

   static $rightname = 'plugin_fusioninventory_networkequipment';


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      if ($this->canView()) {
         return self::createTabEntry(__('FusionInventory SNMP', 'fusioninventory'));
      }
   }



   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      if ($item->getID() > 0) {
         $pfNetworkEquipment = new PluginFusioninventoryNetworkEquipment();

         if (isset($_GET['displaysnmpinfo'])) {
            $pfNetworkEquipment->showNetworkEquipmentInformation($item,
                                                                 array('target'=>$CFG_GLPI['root_doc'].
                                                                       '/plugins/fusioninventory/front/switch_info.form.php'));
         } else {
            $pfNetworkEquipment->showForm($item,
                 array('target'=>$CFG_GLPI['root_doc'].
                                    '/plugins/fusioninventory/front/switch_info.form.php'));
         }
      }

      return TRUE;
   }




   static function getType() {
      return "NetworkEquipment";
   }



   function showForm(CommonDBTM $item, $options=array()) {
      global $DB, $CFG_GLPI;

      if (!Session::haveRight('plugin_fusioninventory_networkequipment', READ)) {
         NetworkPort::showForItem($item);
         return;
      }
      $canedit = FALSE;
      if (Session::haveRight('plugin_fusioninventory_networkequipment', UPDATE)) {
         $canedit = TRUE;
      }

      $id = $item->getID();
     if (!$data = $this->find("`networkequipments_id`='".$id."'", '', 1)) {
         // Add in database if not exist
         $input = array();
         $input['networkequipments_id'] = $id;
         $_SESSION['glpi_plugins_fusinvsnmp_table'] = 'glpi_networkequipments';
         $ID_tn = $this->add($input);
         $this->getFromDB($ID_tn);
      } else {
         foreach ($data as $datas) {
            $this->fields = $datas;
         }
      }

      if (isset($_POST['displaysnmpinfo'])) {
         $this->showNetworkEquipmentInformation($id, $options);
         return;
      }
//$_SESSION['plugin_fusioninventory_networkportview'] = 'glpi';

      if (!isset($_SESSION['plugin_fusioninventory_networkportview'])) {
         $_SESSION['plugin_fusioninventory_networkportview'] = 'fusioninventory';
      }

      // Display glpi network port view if no fusionnetworkport
      $query = "SELECT glpi_plugin_fusioninventory_networkports.id
      FROM glpi_plugin_fusioninventory_networkports
      LEFT JOIN glpi_networkports
      ON glpi_plugin_fusioninventory_networkports.networkports_id = glpi_networkports.id
      WHERE glpi_networkports.items_id='".$id."'
         AND glpi_networkports.itemtype='NetworkEquipment'";
      $result = $DB->query($query);
      if ($DB->numrows($result) == 0) {
         NetworkPort::showForItem($item);
         return;
      }

      echo "<form action='".$CFG_GLPI['root_doc'].
         "/plugins/fusioninventory/front/networkport.display.php' method='post'>";
      echo __('Display the view', 'fusioninventory');
      echo ' <i>'.$_SESSION['plugin_fusioninventory_networkportview']."</i>. ";
      echo __('If you prefer, you can display the view', 'fusioninventory');
      echo ' ';
      if ($_SESSION['plugin_fusioninventory_networkportview'] == 'fusioninventory') {
         echo '<input type="submit" class="submit" name="selectview" value="glpi" />';
      } else {
         echo '<input type="submit" class="submit" name="selectview" value="fusioninventory" />';
      }
      Html::closeForm();

      if ($_SESSION['plugin_fusioninventory_networkportview'] == 'glpi') {
         NetworkPort::showForItem($item);
         return;
      }

      $canedit = $item->can($item->getID(), UPDATE);
      if ($canedit) {
         $networkPort = new NetworkPort();
         echo "\n<form method='get' action='" . $networkPort->getFormURL() ."'>\n";
         echo "<input type='hidden' name='items_id' value='".$item->getID()."'>\n";
         echo "<input type='hidden' name='itemtype' value='".$item->getType()."'>\n";
         echo "<div class='firstbloc'><table class='tab_cadre_fixe'>\n";
         echo "<tr class='tab_bg_2'><td class='center'>\n";
         _e('Network port type to be added');
         echo "&nbsp;";
         Dropdown::showFromArray('instantiation_type',
                                 NetworkPort::getNetworkPortInstantiationsWithNames(),
                                 array('value' => 'NetworkPortEthernet'));
         echo "</td>\n";
         echo "<td class='tab_bg_2 center' width='50%'>";
         _e('Add several ports');
         echo "&nbsp;<input type='checkbox' name='several' value='1'></td>\n";
         echo "<td>\n";
         echo "<input type='submit' name='create' value=\""._sx('button', 'Add')."\" ".
                 "class='submit'>\n";
         echo "</td></tr></table></div>\n";
         Html::closeForm();
      }

      $monitoring = 0;
      if (class_exists("PluginMonitoringNetworkport")) {
         $monitoring = 1;
      }

      // * Get all ports compose tha aggregat
      $a_aggregated_ports = array();
      $query = "SELECT *, glpi_plugin_fusioninventory_networkports.mac as ifmacinternal
      FROM glpi_plugin_fusioninventory_networkports
      LEFT JOIN glpi_networkports
      ON glpi_plugin_fusioninventory_networkports.networkports_id = glpi_networkports.id
      WHERE glpi_networkports.items_id='".$id."'
         AND glpi_networkports.itemtype='NetworkEquipment'
         AND `instantiation_type`='NetworkPortAggregate'
      ORDER BY logical_number ";
      $result = $DB->query($query);
      while ($data = $DB->fetch_array($result)) {
         $query_ag = "SELECT * FROM `glpi_networkportaggregates`
            WHERE `networkports_id`='".$data['id']."'
            LIMIT 1";
         $result_ag = $DB->query($query_ag);
         if ($DB->numrows($result_ag) > 0) {
            $data_ag = $DB->fetch_assoc($result_ag);
            $a_ports = importArrayFromDB($data_ag['networkports_id_list']);
            foreach ($a_ports as $port_id) {
               $a_aggregated_ports[$port_id] = $port_id;
            }
         }
      }

      $where = '';
      if (count($a_aggregated_ports) > 0) {
         $where = "AND `glpi_networkports`.`id` NOT IN ".
                     "('".implode("', '", $a_aggregated_ports)."')";
      }

      $query = "SELECT `glpi_networkports`.`id`, `instantiation_type`,
         `glpi_plugin_fusioninventory_networkports`.`id` as `fusionid`
      FROM glpi_plugin_fusioninventory_networkports

      LEFT JOIN glpi_networkports
         ON glpi_plugin_fusioninventory_networkports.networkports_id = glpi_networkports.id
      WHERE glpi_networkports.items_id='".$id."'
         AND `glpi_networkports`.`itemtype`='NetworkEquipment'
         ".$where."
         AND NOT (glpi_networkports.name='general'
                     AND glpi_networkports.logical_number=0)
      ORDER BY logical_number ";

      $nbcol = 5;
      if ($monitoring == '1') {
         if (Session::haveRight("plugin_monitoring_componentscatalog", READ)) {
            echo "<form name='form' method='post' action='".$CFG_GLPI['root_doc'].
                    "/plugins/monitoring/front/networkport.form.php'>";
            echo "<input type='hidden' name='items_id' value='".$id."' />";
            echo "<input type='hidden' name='itemtype' value='NetworkEquipment' />";
         }
         $nbcol++;
      }

      $a_pref = DisplayPreference::getForTypeUser('PluginFusioninventoryNetworkport',
                                                  Session::getLoginUserID());

      echo "<table class='tab_cadre' cellpadding='".$nbcol."' width='1100'>";

      $result = $this->showNetworkPortDetailHeader($monitoring, $query);

      if ($result) {
         while ($data=$DB->fetch_array($result)) {
            $this->showNetworkPortDetail($data, $monitoring);

            if ($data['instantiation_type'] == 'NetworkPortAggregate') {
               $query_ag = "SELECT * FROM `glpi_networkportaggregates`
                  WHERE `networkports_id`='".$data['id']."'
                  LIMIT 1";
               $result_ag = $DB->query($query_ag);
               if ($DB->numrows($result_ag) > 0) {
                  $data_ag = $DB->fetch_assoc($result_ag);
                  $a_ports = importArrayFromDB($data_ag['networkports_id_list']);
                  foreach ($a_ports as $port_id) {
                     $query_agp = "
                     SELECT `glpi_networkports`.`id`, `instantiation_type`,
                        `glpi_plugin_fusioninventory_networkports`.`id` as `fusionid`

                     FROM glpi_plugin_fusioninventory_networkports

                     LEFT JOIN glpi_networkports
                     ON glpi_plugin_fusioninventory_networkports.networkports_id =
                           glpi_networkports.id
                     WHERE `glpi_networkports`.`id`='".$port_id."'
                     LIMIT 1 ";
                     $result_agp = $DB->query($query_agp);
                     if ($DB->numrows($result_agp) > 0) {
                        $data_agp = $DB->fetch_assoc($result_agp);
                        $this->showNetworkPortDetail($data_agp, $monitoring, TRUE);
                     }
                  }
               }
            }
         }
      }
      if ($monitoring == '1') {
         if (Session::haveRight("plugin_monitoring_componentscatalog", UPDATE)) {
            echo "<tr class='tab_bg_1 center'>";
            echo "<td colspan='2'></td>";
            echo "<td class='center'>";
            echo "<input type='submit' class='submit' name='update' value='".__s('Save')."'/>";
            echo "</td>";
            echo "<td colspan='".(count($a_pref))."'></td>";
            echo "</tr>";
         }
      }
      echo "</table>";
      if ($monitoring == '1') {
         if (Session::haveRight("plugin_monitoring_componentscatalog", UPDATE)) {
            Html::closeForm();
         }
      }
   }



   /**
    * Convert size of octets
    *
    * @param number $bytes
    * @param number $sizeoct
    *
    * @return better size format
    */
   private function byteSize($bytes, $sizeoct=1024) {
      $size = $bytes / $sizeoct;
      if ($size < $sizeoct) {
         $size = number_format($size, 0);
         $size .= ' K';
      } else {
         if ($size / $sizeoct < $sizeoct) {
            $size = number_format($size / $sizeoct, 0);
            $size .= ' M';
         } else if ($size / $sizeoct / $sizeoct < $sizeoct) {
            $size = number_format($size / $sizeoct / $sizeoct, 0);
            $size .= ' G';
         } else if ($size / $sizeoct / $sizeoct / $sizeoct < $sizeoct) {
            $size = number_format($size / $sizeoct / $sizeoct / $sizeoct, 0);
            $size .= ' T';
         }
      }
      return $size;
   }



   function displayHubConnections($items_id, $background_img){

      $NetworkPort = new NetworkPort();

      $a_ports = $NetworkPort->find("`itemtype`='PluginFusioninventoryUnmanaged'
                                    AND `items_id`='".$items_id."'");
      echo "<table width='100%' class='tab_cadre' cellpadding='5'>";
      foreach ($a_ports as $a_port) {
         if ($a_port['name'] != "Link") {
            $id = $NetworkPort->getContact($a_port['id']);
            if ($id) {
               $NetworkPort->getFromDB($id);
               $link = '';
               $link1 = '';
               $link2 = '';
               if ($NetworkPort->fields['itemtype'] == 'PluginFusioninventoryUnmanaged') {
                  $classname = $NetworkPort->fields['itemtype'];
                  $item = new $classname;
                  $item->getFromDB($NetworkPort->fields['items_id']);
                  $link1 = $item->getLink(1);
                  $link = str_replace($item->getName(0), $NetworkPort->fields["mac"],
                                      $item->getLink());
                  // Get ips
                  $a_ips = PluginFusioninventoryToolbox::getIPforDevice(
                             'PluginFusioninventoryUnmanaged',
                             $item->getID()
                          );
                  $link2 = str_replace($item->getName(0), implode(", ", $a_ips),
                                       $item->getLink());
                  $icon = $this->getItemtypeIcon($item->fields["item_type"]);
                  if ($item->fields['accepted'] == 1) {
                     echo "<tr>";
                     echo "<td align='center'  style='background:#bfec75'
                                              class='tab_bg_1_2'>".$icon.$item->getLink(1);

                  } else {
                     echo "<tr>";
                     echo "<td align='center' style='background:#cf9b9b'
                                              class='tab_bg_1_2'>".$icon.$item->getLink(1);
                  }
                  if (!empty($link)) {
                     echo "<br/>".$link;
                  }
                  if (!empty($link2)) {
                     echo "<br/>".$link2;
                  }
                  echo "</td>";
                  echo "</tr>";
               } else {
                  $classname = $NetworkPort->fields['itemtype'];
                  $item = new $classname;
                  $item->getFromDB($NetworkPort->fields['items_id']);
                  $link1 = $item->getLink(1);
                  $link = str_replace($item->getName(0), $NetworkPort->fields["mac"],
                                      $item->getLink());
//                  $link2 = str_replace($item->getName(0), $NetworkPort->fields["ip"],
//                                       $item->getLink());
                  echo "<tr>";
                  $icon = $this->getItemtypeIcon($classname);
                  echo "<td align='center'  ".$background_img."
                                           class='tab_bg_1_2'>".$icon.$item->getLink(1);
                  if (!empty($link)) {
                     echo "<br/>".$link;
                  }
                  if (!empty($link2)) {
                     echo "<br/>".$link2;
                  }
                  echo "</td>";
                  echo "</tr>";

               }
            }
         }
      }
      echo "</table>";
   }



   function update_network_infos($id, $plugin_fusinvsnmp_models_id,
                                 $plugin_fusinvsnmp_configsecurities_id, $sysdescr) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_plugin_fusioninventory_networkequipments`
                WHERE `networkequipments_id`='".$id."';";
      $result = $DB->query($query);
      if ($DB->numrows($result) == "0") {
         $queryInsert = "INSERT INTO `glpi_plugin_fusioninventory_networkequipments`
                            (`networkequipments_id`) VALUES('".$id."');";

         $DB->query($queryInsert);
      }
      if (empty($plugin_fusinvsnmp_configsecurities_id)) {
         $plugin_fusinvsnmp_configsecurities_id = 0;
      }
      $query = "UPDATE `glpi_plugin_fusioninventory_networkequipments`
                SET `plugin_fusioninventory_configsecurities_id`=
                        '".$plugin_fusinvsnmp_configsecurities_id."',
                    `sysdescr`='".$sysdescr."'
                WHERE `networkequipments_id`='".$id."';";

      $DB->query($query);
   }



   function showNetworkEquipmentInformation(CommonDBTM $item, $options) {
      global $DB;

      $id = $item->getID();
      if (!$data = $this->find("`networkequipments_id`='".$id."'", '', 1)) {
         // Add in database if not exist
         $input = array();
         $input['networkequipments_id'] = $id;
         $_SESSION['glpi_plugins_fusinvsnmp_table'] = 'glpi_networkequipments';
         $ID_tn = $this->add($input);
         $this->getFromDB($ID_tn);
      } else {
         foreach ($data as $datas) {
            $this->fields = $datas;
         }
      }


      // Form networking informations
      echo "<form name='form' method='post' action='".$options['target']."'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='4'>";
      echo __('SNMP information', 'fusioninventory');

      echo "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td align='center' rowspan='4'>";
      echo __('Sysdescr', 'fusioninventory')."&nbsp;:";
      echo "</td>";
      echo "<td rowspan='4'>";
      echo "<textarea name='sysdescr' cols='45' rows='5'>";
      echo $this->fields['sysdescr'];
      echo "</textarea>";
      echo "<td align='center'></td>";
      echo "<td align='center'>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td align='center'>".__('SNMP authentication', 'fusioninventory')."&nbsp;:</td>";
      echo "<td align='center'>";
      PluginFusioninventoryConfigSecurity::auth_dropdown(
                 $this->fields['plugin_fusioninventory_configsecurities_id']
              );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td align='center'>";
      echo __('CPU usage (in %)', 'fusioninventory')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Html::displayProgressBar(250, $this->fields['cpu'],
                  array('simple' => TRUE));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td align='center'>";
      echo __('Memory usage (in %)', 'fusioninventory')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      $query2 = "SELECT *
                 FROM `glpi_networkequipments`
                 WHERE `id`='".$id."';";
      $result2 = $DB->query($query2);
      $data2 = $DB->fetch_assoc($result2);
      $ram_pourcentage = 0;
      if (!empty($data2["ram"]) AND !empty($this->fields['memory'])) {
         $ram_pourcentage = ceil((100 * ($data2["ram"] - $this->fields['memory'])) / $data2["ram"]);
      }
      if ((($data2["ram"] - $this->fields['memory']) < 0)
           OR (empty($this->fields['memory']))) {
         echo "<center><strong>".__('Datas not available', 'fusioninventory')."</strong></center>";
      } else {
         Html::displayProgressBar(250, $ram_pourcentage,
                        array('title' => " (".($data2["ram"] - $this->fields['memory'])." Mo / ".
                         $data2["ram"]." Mo)"));
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2 center'>";
      echo "<td colspan='4'>";
      echo "<input type='hidden' name='id' value='".$id."'>";
      echo "<input type='submit' name='update' value=\"".__('Update')."\" class='submit' >";
      echo "</td>";
      echo "</tr>";

      echo "</table>";
      Html::closeForm();
   }



   /**
    * Display informations about networkequipment (automatic inventory)
    *
    * @param type $networkequipments_id
    */
   static function showInfo($item) {

      // Manage locks pictures
      PluginFusioninventoryLock::showLockIcon('NetworkEquipment');

      $pfNetworkEquipment = new PluginFusioninventoryNetworkEquipment();
      $a_networkequipmentextend = current($pfNetworkEquipment->find(
                                              "`networkequipments_id`='".$item->getID()."'",
                                              "", 1));
      if (empty($a_networkequipmentextend)) {
         return;
      }

      echo '<table class="tab_glpi" width="100%">';
      echo '<tr>';
      echo '<th colspan="2">'.__('FusionInventory', 'fusioninventory').'</th>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>';
      echo __('Last inventory', 'fusioninventory');
      echo '</td>';
      echo '<td>';
      echo Html::convDateTime($a_networkequipmentextend['last_fusioninventory_update']);
      echo '</td>';
      echo '</tr>';

      if ($a_networkequipmentextend['uptime'] != '') {
         echo '<tr class="tab_bg_1">';
         echo '<td>'.__('Uptime', 'fusioninventory').'</td>';
         echo '<td>';
         $sysUpTime = $a_networkequipmentextend['uptime'];
         $day = 0;
         $hour = 0;
         $minute = 0;
         $sec = 0;
         $ticks = 0;
         if (strstr($sysUpTime, "days")) {
            list($day, $hour, $minute, $sec, $ticks) = sscanf($sysUpTime, "%d days, %d:%d:%d.%d");
         } else if (strstr($sysUpTime, "hours")) {
            $day = 0;
            list($hour, $minute, $sec, $ticks) = sscanf($sysUpTime, "%d hours, %d:%d.%d");
         } else if (strstr($sysUpTime, "minutes")) {
            $day = 0;
            $hour = 0;
            list($minute, $sec, $ticks) = sscanf($sysUpTime, "%d minutes, %d.%d");
         } else if($sysUpTime == "0") {
            $day = 0;
            $hour = 0;
            $minute = 0;
            $sec = 0;
         } else {
            list($hour, $minute, $sec, $ticks) = sscanf($sysUpTime, "%d:%d:%d.%d");
            $day = 0;
         }

         echo "<b>$day</b> ".__('day(s)', 'fusioninventory')." ";
         echo "<b>$hour</b> ".__('hour(s)', 'fusioninventory')." ";
         echo "<b>$minute</b> ".__('Minute(s)', 'fusioninventory')." ";
         echo " ".__('and')." <b>$sec</b> ".__('sec(s)', 'fusioninventory')." ";

         echo '</td>';
         echo '</tr>';
      }

      echo '</table>';
   }



   function showNetworkPortDetailHeader($monitoring, $query) {
      global $DB, $CFG_GLPI;

      $a_pref = DisplayPreference::getForTypeUser('PluginFusioninventoryNetworkport',
                                                  Session::getLoginUserID());

      echo "<tr class='tab_bg_1'>";

      echo "<th colspan='".(count($a_pref) + 3)."'>";
      echo __('Ports array', 'fusioninventory');

      $result=$DB->query($query);
      echo ' ('.$DB->numrows($result).')';

      $tmp = " class='pointer' onClick=\"var w = window.open('".$CFG_GLPI["root_doc"].
             "/front/popup.php?popup=search_config&amp;".
             "itemtype=PluginFusioninventoryNetworkPort' , 'glpipopup', ".
             "'height=400, width=1000, top=100, left=100, scrollbars=yes'); w.focus();\"";

      echo " <img alt=\"".__s('Select default items to show')."\" title=\"".
                          __s('Select default items to show')."\" src='".
                          $CFG_GLPI["root_doc"]."/pics/options_search.png' ";
      echo $tmp.">";


      $url_legend = "https://forge.indepnet.net/wiki/fusioninventory/".
                        "En_VI_visualisationsdonnees_2_reseau";
      if ($_SESSION["glpilanguage"] == "fr_FR") {
         $url_legend = "https://forge.indepnet.net/wiki/fusioninventory/".
                           "Fr_VI_visualisationsdonnees_2_reseau";
      }
      echo "<a href='legend'></a>";
      echo "<div id='legendlink'><a onClick='Ext.get(\"legend\").toggle();'>".
              "[ ".__('Legend', 'fusioninventory')." ]</a></div>";
      echo "</th>";
      echo "</tr>";

      // Display legend
      echo "
      <tr class='tab_bg_1' style='display: none;' id='legend'>
         <td colspan='".(count($a_pref) + 4)."'>
         <ul>
            <li>".
             __('Connection with a switch or a server in trunk or tagged mode', 'fusioninventory').
             "&nbsp;:</li>
         </ul>
         <img src='".$CFG_GLPI['root_doc']."/plugins/fusioninventory/pics/port_trunk.png' ".
              "width='750' />
         <ul>
            <li>".__('Other connections (with a computer, a printer...)', 'fusioninventory').
              "&nbsp;:</li>
         </ul>
         <img src='".$CFG_GLPI['root_doc']."/plugins/fusioninventory/pics/connected_trunk.png' ".
              "width='750' />
         </td>
      </tr>";
      echo "<script>Ext.get('legend').setVisibilityMode(Ext.Element.DISPLAY);</script>";

      echo "<tr class='tab_bg_1'>";

      echo "<th colspan='2'>".__('Name')."</th>";

      if ($monitoring == '1') {
         echo "<th>".__('Monitoring', 'fusioninventory')."</th>";
      }

      foreach ($a_pref as $data_array) {

         echo "<th>";
         switch ($data_array) {
            case 3:
               echo __('MTU', 'fusioninventory');
               break;

            case 5:
               echo __('Speed');
               break;

            case 6:
               echo __('Internal status', 'fusioninventory');
               break;

            case 7:
               echo __('Last Change', 'fusioninventory');
               break;

            case 8:
               echo __('Traffic received/sent', 'fusioninventory');
               break;

            case 9:
               echo __('Errors received/sent', 'fusioninventory');
               break;

            case 10 :
               echo __('Duplex', 'fusioninventory');
               break;

            case 11 :
               echo __('Internal MAC address', 'fusioninventory');
               break;

            case 12:
               echo __('VLAN');
               break;

            case 13:
               echo __('Connected to');
               break;

            case 14:
               echo __('Connection');
               break;

            case 15:
               echo __('Port not connected since', 'fusioninventory');
               break;

            case 16:
               echo __('Alias', 'fusioninventory');
               break;

         }
         echo "</th>";
      }
      echo "</tr>";
      return $result;
   }


   /**
    * Display detail networkport based on glpi core networkport and fusioninventory
    * networkport
    *
    * @param array $data with id ant fusionid
    * @param boolean $monitoring true if monitoring installed && actived
    * @param boolean $aggrega true if this port is aggregate port
    *
    * @return nothing
    */
   function showNetworkPortDetail($data, $monitoring, $aggrega=0) {
      global $CFG_GLPI, $DB;

      $nw            = new NetworkPort_NetworkPort();
      $networkName   = new NetworkName();
      $networkPort   = new NetworkPort();
      $pfNetworkPort = new PluginFusioninventoryNetworkPort();
      $iPAddress = new IPAddress();

      $networkPort->getFromDB($data['id']);
      $pfNetworkPort->getFromDB($data['fusionid']);

      $background_img = "";
      if (($pfNetworkPort->fields["trunk"] == "1")
                 && (strstr($pfNetworkPort->fields["ifstatus"], "up")
              || $pfNetworkPort->fields["ifstatus"] == 1)) {
         $background_img = " style='background-image: url(\"".$CFG_GLPI['root_doc'].
                              "/plugins/fusioninventory/pics/port_trunk.png\"); '";
      } else if (PluginFusioninventoryNetworkPort::isPortHasMultipleMac($data['id'])
              && (strstr($pfNetworkPort->fields["ifstatus"], "up")
              || $pfNetworkPort->fields["ifstatus"] == 1)) {
         $background_img = " style='background-image: url(\"".$CFG_GLPI['root_doc'].
                              "/plugins/fusioninventory/pics/multiple_mac_addresses.png\"); '";
      } else if (strstr($pfNetworkPort->fields["ifstatus"], "up")
              || $pfNetworkPort->fields["ifstatus"] == 1) {
         $background_img = " style='background-image: url(\"".$CFG_GLPI['root_doc'].
                              "/plugins/fusioninventory/pics/connected_trunk.png\"); '";
      }
      echo "<tr class='tab_bg_1 center' height='40'".$background_img.">";

      if ($aggrega) {
         echo "<td style='background-color: #f2f2f2;'></td><td>";
      }
      if (!$aggrega) {
         if ($networkPort->fields['instantiation_type'] == 'NetworkPortAggregate') {
            echo "<td>";
         } else {
            echo "<td colspan='2'>";
         }
      }
      echo "<a href='networkport.form.php?id=".$networkPort->fields["id"]."'>".
               $networkPort->fields["name"]."</a>";
      Html::showToolTip($pfNetworkPort->fields['ifdescr']);
      if (!$aggrega) {
         if ($networkPort->fields['instantiation_type'] == 'NetworkPortAggregate') {
            echo "<td><i><font style='color: grey'>".__('Aggregation port')."</font></i></td>";
         }
      }

      if ($monitoring == '1') {
         echo "<td>";
         $state = PluginMonitoringNetworkport::isMonitoredNetworkport($data['id']);
         if (Session::haveRight("plugin_monitoring_componentscatalog", UPDATE)) {
            $checked = '';
            if ($state) {
               $checked = 'checked';
            }
            echo "<input type='checkbox' name='networkports_id[]' value='".$data['id']."' ".
                    $checked."/>";
         } else if (Session::haveRight("plugin_monitoring_componentscatalog", READ)) {
            echo Dropdown::getYesNo($state);
         }
         echo "</td>";
      }

      $a_pref = DisplayPreference::getForTypeUser('PluginFusioninventoryNetworkport',
                                                  Session::getLoginUserID());
      foreach ($a_pref as $data_array) {

         switch ($data_array) {
            case 3:
               echo "<td>".$pfNetworkPort->fields["ifmtu"]."</td>";
               break;

            case 5:
               echo "<td>".$this->byteSize($pfNetworkPort->fields["ifspeed"], 1000)."bps</td>";
               break;

            case 6:
               echo "<td>";
               if (strstr($pfNetworkPort->fields["ifstatus"], "up")
                       || strstr($pfNetworkPort->fields["ifinternalstatus"], "1")) {
                  echo "<img src='".$CFG_GLPI['root_doc']."/pics/greenbutton.png'/>";
               } else if (strstr($pfNetworkPort->fields["ifstatus"], "down")
                       || strstr($pfNetworkPort->fields["ifinternalstatus"], "2")) {
                  echo "<img src='".$CFG_GLPI['root_doc']."/pics/redbutton.png'/>";
               } else if (strstr($pfNetworkPort->fields["ifstatus"], "testing")
                       || strstr($pfNetworkPort->fields["ifinternalstatus"], "3")) {
                  echo "<img src='".$CFG_GLPI['root_doc'].
                           "/plugins/fusioninventory/pics/yellowbutton.png'/>";
               }
               echo "</td>";
               break;

            case 7:
               echo "<td>".$pfNetworkPort->fields["iflastchange"]."</td>";
               break;

            case 8:
               echo "<td>";
               if ($pfNetworkPort->fields["ifinoctets"] == "0") {
                  echo "-";
               } else {
                  echo $this->byteSize($pfNetworkPort->fields["ifinoctets"], 1000)."o";
               }
               echo " / ";
               if ($pfNetworkPort->fields["ifinoctets"] == "0") {
                  echo "-";
               } else {
                  echo $this->byteSize($pfNetworkPort->fields["ifoutoctets"], 1000)."o";
               }

               echo "</td>";
               break;

            case 9:
               $color = '';
               if ($pfNetworkPort->fields["ifinerrors"] != "0"
                       || $pfNetworkPort->fields["ifouterrors"] != "0") {
                  $color = "background='#cf9b9b' class='tab_bg_1_2'";
               }
               if ($pfNetworkPort->fields["ifinerrors"] == "0") {
                  echo "<td ".$color.">-";
               } else {
                  echo "<td ".$color.">";
                  echo $pfNetworkPort->fields["ifinerrors"];
               }
               echo " / ";
               if ($pfNetworkPort->fields["ifouterrors"] == "0") {
                  echo "-";
               } else {
                  echo $pfNetworkPort->fields["ifouterrors"];
               }
               echo "</td>";
               break;

            case 10:
               if ($pfNetworkPort->fields["portduplex"] == 2) {
                  echo "<td background='#cf9b9b' class='tab_bg_1_2'>";
                  echo __('Half', 'fusioninventory');
                  echo '</td>';
               } else if ($pfNetworkPort->fields["portduplex"] == 3) {
                  echo '<td>';
                  echo __('Full', 'fusioninventory');
                  echo '</td>';
               } else {
                  echo "<td></td>";
               }
               break;

            case 11:
               // ** internal mac
               echo "<td>".$networkPort->fields["mac"]."</td>";
               break;

            case 13:
               // ** Mac address and link to device which are connected to this port
               $opposite_port = $nw->getOppositeContact($data["id"]);
               if ($opposite_port != ""
                       && $opposite_port!= 0) {
                  $networkPortOpposite = new NetworkPort();
                  if ($networkPortOpposite->getFromDB($opposite_port)) {
                     $data_device = $networkPortOpposite->fields;
                     $item = new $data_device["itemtype"];
                     $item->getFromDB($data_device["items_id"]);
                     $link1 = $item->getLink(1);
                     $link = str_replace($item->getName(0), $data_device["mac"],
                                         $item->getLink());

                     // * GetIP
                        $a_networknames = current($networkName->find("`itemtype`='NetworkPort'
                                          AND `items_id`='".$item->getID()."'", "", 1));
                        $a_ipaddresses =  current($iPAddress->find("`itemtype`='NetworkName'
                                          AND `items_id`='".$a_networknames['id']."'", "", 1));
                        $link2 = str_replace($item->getName(0), $a_ipaddresses['name'],
                                             $item->getLink());

                     if ($data_device["itemtype"] == 'PluginFusioninventoryUnmanaged') {
                        $icon = $this->getItemtypeIcon($item->fields["item_type"]);
                        if ($item->getField("accepted") == "1") {
                           echo "<td style='background:#bfec75'
                                     class='tab_bg_1_2'>".$icon.$link1;
                        } else {
                           echo "<td background='#cf9b9b'
                                     class='tab_bg_1_2'>".$icon.$link1;
                        }
                        if (!empty($link)) {
                           echo "<br/>".$link;
                        }
                        if (!empty($link2)) {
                           echo "<br/>".$link2;
                        }
                        if ($item->getField("hub") == "1") {
                           $this->displayHubConnections($data_device["items_id"], $background_img);
                        }
                        echo "</td>";
                     } else {
                        $icon = $this->getItemtypeIcon($data_device["itemtype"]);

                        echo "<td>".$icon.$link1;
                        if (!empty($link)) {
                           echo "<br/>".$link;
                        }
                        if (!empty($link2)) {
                           echo "<br/>".$link2;
                        }
                        if ($data_device["itemtype"] == 'Phone') {
                           $query_devicephone = "SELECT *
                                   FROM `glpi_networkports`
                                   WHERE `itemtype`='Phone'
                                       AND `items_id`='".$data_device["items_id"]."'
                                       AND `id`!='".$data_device["id"]."'
                                   LIMIT 1";
                           $result_devicephone = $DB->query($query_devicephone);
                           if ($DB->numrows($result_devicephone) > 0) {
                              $data_devicephone = $DB->fetch_assoc($result_devicephone);
                              $computer_ports_id = $nw->getOppositeContact($data_devicephone["id"]);
                              if ($computer_ports_id) {
                                 $networkport = new NetworkPort();
                                 $networkport->getFromDB($computer_ports_id);
                                 if ($networkport->fields['itemtype'] == 'Computer') {
                                    echo "<hr/>";
                                    echo "<img src='".$CFG_GLPI['root_doc'].
                                            "/plugins/fusioninventory/pics/computer_icon.png' ".
                                            "style='float:left'/> ";
                                    $computer = new Computer();
                                    $computer->getFromDB($networkport->fields["items_id"]);
                                    $link1 = $computer->getLink(1);
                                    $link = str_replace($computer->getName(0),
                                                        $networkport->fields["mac"],
                                                        $computer->getLink());
                                    $link2 = str_replace($computer->getName(0),
                                                         $networkport->fields["ip"],
                                                         $computer->getLink());

                                    echo $icon.$link1;
                                    if (!empty($link)) {
                                       echo "<br/>".$link;
                                    }
                                    if (!empty($link2)) {
                                       echo "<br/>".$link2;
                                    }
                                 }
                              }
                           }
                        }
                        echo "</td>";
                     }
                  } else {
                     echo "<td></td>";
                  }
               } else {
                  echo "<td></td>";
               }
               break;

            case 14:
               // ** Connection status
               echo "<td>";
               if (strstr($pfNetworkPort->fields["ifstatus"], "up")
                       || strstr($pfNetworkPort->fields["ifstatus"], "1")) {
                  echo "<img src='".$CFG_GLPI['root_doc'].
                          "/plugins/fusioninventory/pics/wired_on.png'/>";
               } else if (strstr($pfNetworkPort->fields["ifstatus"], "down")
                       || strstr($pfNetworkPort->fields["ifstatus"], "2")) {
                  echo "<img src='".$CFG_GLPI['root_doc'].
                          "/plugins/fusioninventory/pics/wired_off.png'/>";
               } else if (strstr($pfNetworkPort->fields["ifstatus"], "testing")
                       || strstr($pfNetworkPort->fields["ifstatus"], "3")) {
                  echo "<img src='".$CFG_GLPI['root_doc'].
                          "/plugins/fusioninventory/pics/yellowbutton.png'/>";
               } else if (strstr($pfNetworkPort->fields["ifstatus"], "dormant")
                       || strstr($pfNetworkPort->fields["ifstatus"], "5")) {
                  echo "<img src='".$CFG_GLPI['root_doc'].
                          "/plugins/fusioninventory/pics/orangebutton.png'/>";
               }
               echo "</td>";
               break;

            case 12:
               echo "<td>";

               $canedit = Session::haveRight('networking', UPDATE);

               $used = array();

               $query_vlan = "SELECT * FROM glpi_networkports_vlans
                              WHERE networkports_id='".$data["id"]."'";
               $result_vlan = $DB->query($query_vlan);
               if ($DB->numrows($result_vlan) > 0) {
                  echo "<table cellpadding='0' cellspacing='0'>";
                  while ($line = $DB->fetch_array($result_vlan)) {
                     $used[]=$line["vlans_id"];
                     $vlan = new Vlan();
                     $vlan->getFromDB($line["vlans_id"]);
                     if ($line['tagged'] == '1') {
                        $state = 'T';
                     } else {
                        $state = 'U';
                     }
                     echo "<tr><td>" . $vlan->fields['name']." [".$vlan->fields['tag']."] " . $state;
                     echo "</td><td>";
                     if ($canedit) {
                        echo "<a href='" . $CFG_GLPI["root_doc"].
                                "/front/networkport.form.php?unassign_vlan=unassigned&amp;id=".
                                $line["id"] . "'>";
                        echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/delete.png\" alt='".
                                __('Delete', 'fusioninventory') . "' title='" .
                                __('Delete', 'fusioninventory') . "'></a>";
                     } else {
                        echo "&nbsp;";
                     }
                     echo "</td>";
                     echo "</tr>";
                  }
                  echo "</table>";
               } else {
                  echo "&nbsp;";
               }
               echo "</td>";
               break;

            case 15:
               echo "<td align='center'>";
               if ($pfNetworkPort->fields['ifstatus'] == 1) {
                  echo __('Connected');
               } else if ($pfNetworkPort->fields['lastup'] == "0000-00-00 00:00:00") {
                  echo '-';
               } else {
                  $time = strtotime(date('Y-m-d H:i:s'))
                              - strtotime($pfNetworkPort->fields['lastup']);
                  echo Html::timestampToString($time, FALSE);
               }
               echo "</td>";
               break;

            case 16:
               echo "<td>".$pfNetworkPort->fields["ifalias"]."</td>";
               break;
         }
      }
      echo "</tr>";
   }



   function displaySerializedInventory($items_id) {
      global $CFG_GLPI;

      $a_networkequipmentextend = current($this->find("`networkequipments_id`='".$items_id."'",
                                               "", 1));

      $this->getFromDB($a_networkequipmentextend['id']);

      if (empty($this->fields['serialized_inventory'])) {
         return;
      }

      $data = unserialize(gzuncompress($this->fields['serialized_inventory']));

      echo "<br/>";

      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2'>";
      echo __('Last inventory', 'fusioninventory');
      echo " (".Html::convDateTime($this->fields['last_fusioninventory_update']).")";
      echo "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>";
      echo __('Download', 'fusioninventory');
      echo "</th>";
      echo "<td>";
      echo "<a href='".$CFG_GLPI['root_doc'].
              "/plugins/fusioninventory/front/send_inventory.php".
              "?itemtype=PluginFusioninventoryNetworkEquipment".
              "&function=sendSerializedInventory&items_id=".$a_networkequipmentextend['id'].
              "&filename=NetworkEquipment-".$items_id.".json'".
              "target='_blank'>PHP Array</a> ";

      $folder = substr($items_id, 0, -1);
      if (empty($folder)) {
         $folder = '0';
      }
      if (file_exists(GLPI_PLUGIN_DOC_DIR."/fusioninventory/xml/NetworkEquipment/".$folder."/".$items_id)) {
         echo "/ <a href='".$CFG_GLPI['root_doc'].
        "/plugins/fusioninventory/front/send_inventory.php".
        "?itemtype=networkequipment".
        "&function=sendXML&items_id=NetworkEquipment/".$folder."/".$items_id.
        "&filename=NetworkEquipment-".$items_id.".xml'".
        "target='_blank'>XML</a>";
      }


      echo "</td>";
      echo "</tr>";

      PluginFusioninventoryToolbox::displaySerializedValues($data);

      echo "</table>";
   }



   function getItemtypeIcon($itemtype) {
      global $CFG_GLPI;

      $icon = '';
      if ($itemtype == 'Computer') {
         $icon = "<img src='".$CFG_GLPI['root_doc'].
                 "/plugins/fusioninventory/pics/computer_icon.png' ".
                 "style='float:left'/> ";
      } else if ($itemtype == 'Printer') {
         $icon = "<img src='".$CFG_GLPI['root_doc'].
                 "/plugins/fusioninventory/pics/printer_icon.png' ".
                 "style='float:left'/> ";
      } else if ($itemtype == 'Phone') {
         $icon = "<img src='".$CFG_GLPI['root_doc'].
                 "/plugins/fusioninventory/pics/phone_icon.png' ".
                 "style='float:left'/> ";
      } else if ($itemtype == 'NetworkEquipment') {
         $icon = "<img src='".$CFG_GLPI['root_doc'].
                 "/plugins/fusioninventory/pics/network_icon.png' ".
                 "style='float:left'/> ";
      }
      return $icon;
   }
}

?>
