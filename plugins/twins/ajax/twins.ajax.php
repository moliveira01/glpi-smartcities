<?php
//============================================================================//
//==    Plugin pour GLPI - Dévelloppeur: Viduc (Fleury Tristan) - ©2013     ==//
//==            http://viduc.sugarbox.fr - viduc@sugarbox.fr                ==//
//============================================================================//


/**
 * Traite toute les demandes ajax du plugin
 */
define('GLPI_ROOT', getAbsolutePath());
include (GLPI_ROOT."inc/includes.php");

//Instanciation de la class twins

if(isset($_POST['version'])){
    if($_POST['version'] == "old"){
        $twins = new PluginTwinsTwinsold();
    }
}
else{$twins = new PluginTwinsTwins();}

if(isset($_POST['action'])){
    //envoie la liste des ordinateurs
    if($_POST['action'] == "on"){
        $retour = "<table class='tab_cadre_fixe'><tr><td align=\"center\">";
        $retour .= "<FORM><SELECT id=\"computer\" name=\"computer\" size=\"1\">";
        foreach ($twins->getListeOrdinateur($_POST['idOrdinateur']) as $key => $value) 
            {$retour .= "<OPTION value=\"$key\">$value | $key</OPTION>";}
        $retour .= "</SELECT></FORM></td></tr>";
        $retour .= "<tr><td align=\"center\"><button rel=tooltip title=\"Valider\" id=\"btn-valider\" onclick=\"cloner('validation')\">";
        $retour .= "<img alt='' title=\"Valider\" src='".getHttpPath()."pics/ok.png'>";
        $retour .= "</td></tr></table>";
        echo $retour;
    }
    //envoie les informations de l'ordinateur a cloner    
    elseif($_POST['action'] == "validation"){
        $retour = null;
        $info = $twins->getInfoOrdinateur($_POST['idOrdinateur']);
        if($info){
            $info = $twins->getValueInfoOrdinateur($info);
            $retour = "<table class='tab_cadre_fixe'>";
            $retour .= "<tr><td style=\"width: auto\"></td><td align='center' style=\"width: 600px\">";
            $retour .= "<table class='tab_cadre_AD'><tr><td colspan='4' align=\"center\">";
            $retour .= "Etes vous sur de vouloir cloner l'ordinateur <span class=\"style_AA1\">".$info["name"]."</span> ?</td></tr>";
            $retour .= "<tr><td colspan='4' align=\"center\">Les informations utilisées seront les suivantes:</td></tr>";
            $retour .= "<tr><td align=\"right\" width='25%' class=\"tab_td_AD1\">Lieu</td><td align=\"left\" width='25%'>".$info["lieu"]."</td>";
            $retour .= "<td align=\"right\" width='25%' class=\"tab_td_AD1\">Responsable technique</td><td align=\"left\" width='25%'>".$info["tech"]."</td></tr>";
            $retour .= "<tr><td align=\"right\" width='25%' class=\"tab_td_AD1\">Usager Numéro</td><td align=\"left\" width='25%'>".$info["usager_num"]."</td>";
            $retour .= "<td align=\"right\" width='25%' class=\"tab_td_AD1\">Usager</td><td align=\"left\" width='25%'>".$info["usager"]."</td></tr>";
            $retour .= "<tr><td align=\"right\" width='25%' class=\"tab_td_AD1\">Utilisateur</td><td align=\"left\" width='25%'>".$info["utilisateur"]."</td>";
            $retour .= "<td align=\"right\" width='25%' class=\"tab_td_AD1\">Domaine</td><td align=\"left\" width='25%'>".$info["domaine"]."</td></tr>";
            $retour .= "</table>";
            $retour .= "</td><td style=\"width: auto\"></td></tr></table>";
            $retour .= "<input type=\"hidden\" id=\"idClonage\" value=\"".$info['id']."\">";
            $retour .= getVueGestionGroupe($info['groupe'],$info['id']);
        }
        echo $retour;
    } 
    //modification des groupes AD    
    elseif($_POST['action'] == "add" || $_POST['action'] == "supp"){
        echo getVueGestionGroupe($twins->changeGroupe($_POST['action'],$_POST['groupe'],
            $_POST['groupeModif'],$_POST['id']));
    }
    //Clonage de la machine   
    elseif($_POST['action'] == "cloner"){
        if($twins->clonerOrdinateur($_POST['idOrdinateur'],$_POST['idCloner'],$_POST['groupe']))
            {echo "La machine a été clonée avec succès";}
        else {echo "Une erreur est survenue, contactez votre administrateur système";}
    }
    //impression de létiquette 
    elseif($_POST['action'] == "impression"){
        echo $twins->impression($_POST['idOrdinateur']);
    }
}
    
/**
 * Fonction de mise en forme pour la gestion des groupes
 * @param array $groupe 
 * @return string HTML pour ajax
 */
function getVueGestionGroupe($groupe, $id)
{
    $groupeString = null;
    if($groupe!=""){
        foreach ($groupe[0] as $groupeOrdi){$groupeString .= $groupeOrdi.",";}
        $groupeString = substr($groupeString, 0, -1); 
        $groupeString .= "|";
        foreach ($groupe[1] as $groupeAD){$groupeString .= $groupeAD.",";}
        $groupeString = substr($groupeString, 0, -1);

        $retour = "<div id=\"txtHint\">";
        $retour .= "<table class='tab_cadre_fixe'>";
        $retour .= "<tr>";
        $retour .= "<th colspan=\"2\">Gestion des groupes de l'ordinateur</th>";
        $retour .= "</tr>";
        $retour .= "<tr>";
        $retour .= "<td><table width=\"430px\"><TH>Membre de</TH></table></td>";
        $retour .= "<td><table width=\"430px\"><TH>Groupes disponibles</TH></table></td>";
        $retour .= "</tr>";
        $retour .= "<tr>";
        $retour .= "<td>";
        $retour .= "<div style=\"height: 200px; width: 450px; overflow-y: scroll; overflow-x: hidden\" name=\"groupeOrdinateur\">";
        $retour .= "<table width=\"430px\">";

        foreach ($groupe[0] as $groupeOrdi){
            $retour .= "<TR>";
            $retour .= "<TD width=\"400px\">$groupeOrdi</TD>";
            $retour .= "<TD width=\"30px\"><button id=\"btn-suppGroupe\" onclick=\"change('supp','$groupeString','$groupeOrdi','$id')\"><img alt='' title=\"Retirer\" src='".getHttpPath()."/pics/right.png'></button></TD>";
            $retour .= "</TR>";
        }
        $retour .= "</table>"; 
        $retour .= "</div>";
        $retour .= "</td>";
        $retour .= "<td>";
        $retour .= "<div style=\"height: 200px; width: 450px; overflow-y: scroll; overflow-x: hidden\" name=\"groupeAD\">";
        $retour .= "<table width=\"430px\">";

        foreach ($groupe[1] as $groupeLDAP){
            $retour .= "<TR>";
            $retour .= "<TD width=\"30px\"><button id=\"btn-addGroupe\" onclick=\"change('add','$groupeString','$groupeLDAP','$id')\"><img alt='' title=\"Ajouter\" src='".getHttpPath()."/pics/left.png'></button></TD>";
            $retour .= "<TD width=\"400px\">$groupeLDAP</TD>";
            $retour .= "</TR>";
        }
        $retour .= "</table>";
        $retour .= "</div>";
        $retour .= "</td>";
        $retour .= "</tr>";
    }
    $retour .= "<tr><td colspan='4' align=\"center\">";
    $retour .= "<button rel=tooltip title=\"Valider\" id=\"btn-valider\" onclick=\"cloner('cloner')\">";
    $retour .= "<img alt='' title=\"Valider\" src='".getHttpPath()."pics/ok.png'>";
    $retour .= "</td></tr>";
    $retour .= "</table></div>";
    $retour .= "<input type=\"hidden\" id=\"groupe\" value=\"".$groupeString."\">";
    return $retour;
}
    
/**
 * Récupère le chemin absolue de l'instance glpi
 * @return String : le chemin absolue (racine principale)
 */
function getAbsolutePath()
{return str_replace("plugins/twins/ajax/twins.ajax.php", "", $_SERVER['SCRIPT_FILENAME']);}

/**
 * Récupère le chemin http absolu de l'application glpi
 * @return string : le chemin http absolue de l'application
 */
function getHttpPath()
{
    $temp = explode("/",$_SERVER['HTTP_REFERER']);
    $Ref = "";
    foreach ($temp as $value){
        if($value != "front"){$Ref.= $value."/";}
        else{break;}
    }
    return $Ref;
}
?>
