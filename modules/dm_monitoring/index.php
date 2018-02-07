<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.4.0-11                                               |
  | http://www.elastix.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1 2014-02-27 12:02:11 Supme supmea@gmail.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoConfig.class.php";
include_once "modules/agent_console/libs/paloSantoConsola.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/Monitoring.class.php";

    //include file language agree to elastix configuration
    //if file language not exists, then include language by default (en)
    $lang=get_language();
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $lang_file="modules/$module_name/lang/$lang.lang";
    if (file_exists("$base_dir/$lang_file")) include_once "$lang_file";
    else include_once "modules/$module_name/lang/en.lang";

    //global variables
    global $arrConf;
    global $arrConfModule;
    global $arrLang;
    global $arrLangModule;
    $arrConf = array_merge($arrConf,$arrConfModule);
    $arrLang = array_merge($arrLang,$arrLangModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    //actions
    $action = getAction();
    $content = "";

    $params=explode(':',getParameter('campaign'));
    $type = $params[0];
    $id = $params[1];

    switch($action){
        case "show":

            $content = viewStatMonitoring($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $type, $id);
            break;
        case "loadstat":
            $content = viewStatMonitoring($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $type, $id);
            break;
        case "loadoper":
            //$content = time();
            $content = viewOperMonitoring($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $type, $id);
            break;
        default:
            $content = viewFormMonitoring($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

function viewFormMonitoring($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $Monitoring = new Monitoring($pDB);
    $arrFormMonitoring = createFieldForm($Monitoring->getCampaigns(), $arrConf);
    $oForm = new paloForm($smarty,$arrFormMonitoring);

    //translate
    $smarty->assign('CampaignStatisticPerDay',_tr('Campaign statistic per day'));
    $smarty->assign('AgentsActivity',_tr('Agents activity'));

    $content = $oForm->fetchForm("$local_templates_dir/form.tpl",_tr("DM_Monitoring"));

    return $content;
}

function viewStatMonitoring($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $type, $id){

    $Monitoring = new Monitoring($pDB);

    $result = $Monitoring->statCampaign($type, $id);

    $res_status = array();
    foreach($result['status'] as $key => $res){
        $res_status[] = array('status' => _tr($key) , 'count' => $res);
    }

    $smarty->assign("stat", $res_status);

    // translate
    $smarty->assign('Status',_tr('Status'));
    $smarty->assign('Count',_tr('Count'));

    return $smarty->fetch("file:$local_templates_dir/stat.tpl");
}

function viewOperMonitoring($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $type, $id){

    $oPaloConsola = new PaloSantoConsola();

    $result = $oPaloConsola->leerEstadoCampania($type, $id);

    $Monitoring = new Monitoring($pDB);
    $agents = $Monitoring->getAgents();

    $res_online_agents = array();
    $res_offline_agents = array();
    foreach($result["agents"] as $key => $res){
        $sip = '<strong>'.$agents[$key].'</strong> <small>'.$key.'</small>';
        if($res['status'] == 'offline')
        {
            $res_offline_agents[$sip] = $result["agents"][$key];
            $res_offline_agents[$sip]['status'] = _tr($res["status"]);
            $res_offline_agents['</strong> '.$sip]['lenght'] = NULL;

        } else {
            /* Добавим к перерыву название перерыва
                        if($res['status'] == 'paused')
                            $result["agents"][$key]["status"] = _tr("paused").'('.$res["pausename"].')';
                        else
                            $result["agents"][$key]["status"] = _tr($res["status"]);
                        */
            // Не будем добавлять слово "Перерыв"
            if($res['status'] == 'paused')
            {
                $result["agents"][$key]["status"] = '<strong><span style="color:red">'.$res["pausename"].'</span></strong>';
            } else {
                $result["agents"][$key]["status"] = _tr($res["status"]);
            };
            // Поле с длительностью
            $start = NULL;
            if($res['pausestart']) $start = $res['pausestart'];
            if($res['linkstart']) $start = $res['linkstart'];
            if($res['dialstart']) $start = $res['dialstart'];
            $result["agents"][$key]['lenght'] = $start?gmdate('H:i:s',time()-strtotime($start)):NULL;
            // Отобразим агента по имени
            $res_online_agents[$sip] = $result["agents"][$key];
        }
    }
//echo '<pre>';var_dump($result);echo '</pre>';
    $res_agents = array_merge($res_online_agents, $res_offline_agents);

    $smarty->assign("activecalls", $result['activecalls']);
    $smarty->assign("agents", $res_agents);

    // translate table
    $smarty->assign('Number',_tr('Number'));
    $smarty->assign('Trunk',_tr('Trunk'));
    $smarty->assign('Start',_tr('Start'));
    $smarty->assign('Status',_tr('Status'));
    $smarty->assign('Agent',_tr('Agent'));
    $smarty->assign('Lenght',_tr('Lenght'));
    $smarty->assign('CallNumber',_tr('Call number'));
    $smarty->assign('WaitingResponce',_tr('Waiting responce'));
    $smarty->assign('AgentStatus',_tr('Agent status'));

    return $smarty->fetch("file:$local_templates_dir/oper.tpl");
}


function createFieldForm($campaigns, $arrConf)
{
    foreach ($campaigns as $key => $campaign) {
        $arrCampaigns[$campaign['type'].':'.$campaign['id']] = $campaign['name'];
    }

    $arrFields = array(
        "campaign" => array(
            "LABEL"                  => _tr("Campaign"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrCampaigns,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
            );

    return $arrFields;
}

function getAction()
{
    if(isset($_POST['show']))
        return 'show';
    if(isset($_POST['action']))
        switch($_POST['action']){
            case 'loadstat':
                return "loadstat";
            break;

            case 'loadoper':
                return "loadoper";
            break;

            default:
                return '';
            break;
        }
}
?>