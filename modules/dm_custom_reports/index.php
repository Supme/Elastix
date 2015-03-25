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
  $Id: index.php,v 1.1 2014-01-31 12:01:59 supme supmea@gmail.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoConfig.class.php";
include_once "libs/paloSantoQueue.class.php";

function _moduleContent(&$smarty, $module_name)
{

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/Custom_Reports.class.php";
    include_once "modules/$module_name/libs/Excel_Xml.php";
    include_once "modules/$module_name/libs/Download.class.php";

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

    if(isset($_GET['fileId'])){
        getRecord($_GET['fileId'], $arrConf);
        exit(0);
    }

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);

/*
    //actions
    $action = getAction();
    $content = "";

    switch($action){
        default:
            $content = reportCustom_Reports($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
*/
    return reportCustom_Reports($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function reportCustom_Reports($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
$start = microtime(true);

    $pCustom_Reports = new Custom_Reports($pDB);

    //Параметры для грида
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->pagingShow(false); // не показывать пагинатор.
    $oGrid->enableExport();    // включить экспорт результатов.

    //begin данные для фильтра
    $oFilterForm = new paloForm($smarty, createFieldFilter($pCustom_Reports->getCampaignIn(), $pCustom_Reports->getCampaignOut(), $pCustom_Reports->getAgents(),$pCustom_Reports->getIvrs()));
    $smarty->assign('show', _tr('Show'));
    $htmlFilter  = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end данные для фильтра

    $oGrid->showFilter(trim($htmlFilter));

    //    $isExport = $oGrid->isExportAction();

    //Добавляем в урл страницы дополнительные параметры
    $params = array(
        "menu"      => $module_name,
        "queue_in"  => getParameter("queue_in"),
        "queue_out" => getParameter("queue_out"),
        "date_start"=> getParameter("date_start"),
        "date_end"  => getParameter("date_end"),
        "report"    => getParameter("report"),
        "span"      => getParameter("span"),
        "agent"     => getParameter("agent"),
        "ivr"       => getParameter('ivr'),
    );

    // Передаем параметры фильтру
    $pCustom_Reports->setParams($params["queue_in"], $params["queue_out"], $params['date_start'], $params['date_end'], $params['report'], $params['span'], $params['agent'], $params['ivr']);

    //Столбцы для отображения в гриде
    $Columns = $pCustom_Reports->getColumns_Reports();
    foreach($Columns as $column){
        $arrColumns[] = _tr($column);
    }

    // Получаем данные
    $arrResult =$pCustom_Reports->getCustom_Reports();
    if(is_array($arrResult)){
        foreach($arrResult as $key => $value){
            $i=0;
            foreach($Columns as $column){
                $arrTmp[$i] =  $value[$column];
                $i++;
            }
            $arrData[] = $arrTmp;
        }
    }

    $oGrid->setURL($params);
    $oGrid->setData($arrData);
    $oGrid->setColumns($arrColumns);
    $oGrid->setTitle(_tr("Custom Reports"));
    $oGrid->setNameFile_Export(_tr("report"));

//printf('Скрипт выполнялся %.4F сек.', microtime(true) - $start);

    // Так как с штатным экспортом отчетов проблема, перехватываем стандартный экспорт и делаем свой...
    // ToDo Когда наладят можно убрать
    $file = _tr("report").'_'.date("d-m-Y_H:i:s", time());
    switch($oGrid->exportType()){
        case "csv":
            exportCSV($file, $arrColumns, $arrData);
            break;

        case "xls":
            exportXLS($file, $arrColumns, $arrData);
            break;

        default:
            return $oGrid->fetchGrid();
            break;
    }
}

function createFieldFilter($campaign_in, $campaign_out, $agents, $ivrs){

    $arrCampaign_in = array('0' => '('._tr('No').')', 'all' => '('._tr('All').')');
    foreach ($campaign_in as $oCampaign_in) {
        $arrCampaign_in[$oCampaign_in['id']] = $oCampaign_in['name'];
    }

    $arrCampaign_out = array('0' => '('._tr('No').')', 'all' => '('._tr('All').')');
    foreach ($campaign_out as $oCampaign_out) {
        $arrCampaign_out[$oCampaign_out['id']] = $oCampaign_out['name'];
    }

    $arrAgents = array('' => '('._tr('All').')');
    foreach ($agents as $agent) {
        $arrAgents[$agent['id']] = $agent['name'];
    }

    $arrIvr = array('' => '('._tr('All').')');
    foreach ($ivrs as $ivr) {
        $arrIvr[$ivr['ivr_id']] = $ivr['ivr_name'];
    }

    $arrReport = array(
        'calls'          =>  _tr('Calls'),
        'oncalls'   =>  _tr('onCalls'),
        'ivr' =>  _tr('IVR'),
        'agentlogin' => _tr('AgentLogin'),
        'agentbreak' => _tr('AgentBreak'),
        'volvo' => _tr('Volvo'),
    );

    $arrSpan = array(
        ''          =>  '('._tr('All').')',
        'mon'       =>  _tr('Mounth'),
        'week'      =>  _tr('Week'),
        'day'       =>  _tr('Day'),
        'hour'      =>  _tr('Hour'),
        'ring'      =>  _tr('Ring'),
    );

    $arrFormElements = array(
        "report" => array(
            "LABEL"                  => _tr("Report"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrReport,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "span" => array(
            "LABEL"                  => _tr("Span"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrSpan,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "date_start" => array(
            "LABEL"                  => _tr("Date start"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "DATE",
            "INPUT_EXTRA_PARAM"      => array("TIME" => true, "FORMAT" => "%d %b %Y %H:%M","TIMEFORMAT" => "24"),
            "VALIDATION_TYPE"        => "",
            "EDITABLE"               => "si",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "date_end"   => array(
            "LABEL"                  => _tr("Date end"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "DATE",
            "INPUT_EXTRA_PARAM"      => array("TIME" => true, "FORMAT" => "%d %b %Y %H:%M","TIMEFORMAT" => "24"),
            "VALIDATION_TYPE"        => "",
            "EDITABLE"               => "si",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "queue_in" => array(
            "LABEL"                  => _tr("Campaign_in"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrCampaign_in,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "queue_out" => array(
            "LABEL"                  => _tr("Campaign_out"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrCampaign_out,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "agent" => array(
            "LABEL"                  => _tr("Agent"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrAgents,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "ivr" => array(
            "LABEL"                  => _tr("Ivr"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrIvr,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
    );
    return $arrFormElements;
}

function getAction()
{
    if(getParameter("save_new")) //Get parameter by POST (submit)
        return "save_new";
    else if(getParameter("save_edit"))
        return "save_edit";
    else if(getParameter("delete")) 
        return "delete";
    else if(getParameter("new_open")) 
        return "view_form";
    else if(getParameter("action")=="view")      //Get parameter by GET (command pattern, links)
        return "view_form";
    else if(getParameter("action")=="view_edit")
        return "view_form";
    else
        return "report"; //cancel
}
// CSV экспорт --------------------------------------------------------
function exportCSV($file, $arrColumns, $arrData)
{
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=".$file.".csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    $outstream = fopen("php://output", "w");
    fputcsv($outstream, exportConvert($arrColumns), ';');
    foreach($arrData as $data){
        $data = exportConvert($data);
        fputcsv($outstream, $data,';');
    }
    fclose($outstream);
}

function exportConvert($data, $conv = false)
{
    foreach($data as $index => $val){
        $search  = array("<b>",  "</b>", "&nbsp;");
        $replace = array("",     "",      " ");
        $val = str_replace($search, $replace, $val);
        $data[$index] = $conv?$val:iconv("UTF8", "CP1251", $val);
    }
    return $data;
}

//XSL экспорт
function exportXLS($file, $arrColumns, $arrData)
{
    $phpexcel = new Excel_Xml;
    array_unshift($arrData, $arrColumns);
    $phpexcel->addWorksheet(_tr('report'), exportConvert($arrData, true));
    $phpexcel->sendWorkbook($file.'.xls');
}

function getRecord($fileId, $arrConf){
/*
    $pDB = new paloDB(generarDSNSistema('asteriskuser', 'asteriskcdrdb'));

    $query = "SELECT userfield FROM cdr WHERE uniqueid=? AND userfield != ''";
    $result = $pDB->getFirstRowQuery($query,true,array($fileId));

    $file = basename(str_replace('audio:', '', $result['userfield']));
    $file = str_replace($arrConf['records_dir'], '', $file);
    $path = $arrConf['records_dir'].$file;


    // Set Content-Type according to file extension
    $contentTypes = array(
        'wav'   =>  'audio/x-wav',
        'gsm'   =>  'audio/x-gsm',
        'mp3'   =>  'audio/mpeg',
    );
    $extension = substr(strtolower($file), -3);

    if (!isset($contentTypes[$extension])) {
        $path .= '.wav';
        $file .= '.wav';
        $extension  = 'wav';
    }
*/
    $path = glob($arrConf['records_dir'].'*'.$fileId.'*');

    if(isset($path[0])){
        $file = str_replace($arrConf['records_dir'], '', $path[0]);
        $in_browser = isset($_GET['download'])?true:false;
        $download = new Download($path[0], $file, $in_browser);
        $download->download_file();
    } else {
        Header('HTTP/1.1 404 Not Found');
        die("<b>404 "._tr("no_file")." </b>");
    }

/*
    // Actually open and transmit the file
    $fp = fopen($path, 'rb');
    if (!$fp) {
        Header('HTTP/1.1 404 Not Found');
        die("<b>404 "._tr("no_file")." </b>");
    }
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: wav file");
    header("Content-Type: " . $contentTypes[$extension]);
    header("Content-Disposition: attachment; filename=" . $file);
    header("Content-Transfer-Encoding: binary");
    header("Content-length: " . filesize($path));
    fpassthru($fp);
    fclose($fp);*/
}
?>