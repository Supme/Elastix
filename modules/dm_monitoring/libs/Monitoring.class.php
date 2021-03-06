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
  $Id: Custom_Reports.class.php,v 1.1 2014-01-31 12:01:59 supme supmea@gmail.com Exp $ */
class Monitoring{
    var $_DB;
    var $errMsg;
    var $campaign = false;
    var $action;
    var $module_name;

    function Monitoring(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    // Принимаем параметры
    function setParams($campaign, $type, $module_name){
        $this->campaign = $campaign;
        $this->module_name = $module_name;
    }

    // Список кампаний
    function getCampaigns(){

        $query   = "SELECT id, name FROM campaign";
        $result_out = $this->_DB->fetchTable($query, true);

        $query   = "SELECT id, name FROM campaign_entry";
        $result_in = $this->_DB->fetchTable($query, true);

        $result = array();

        foreach($result_in as $tmp){
            $tmp['type'] = 'incoming';
            $result[] = $tmp;
        }

        foreach($result_out as $tmp){
            $tmp['type'] = 'outgoing';
            $result[] = $tmp;
        }

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        return $result;
    }

    // Список агентов
    function getAgents(){

        $query   = "SELECT type, number, name FROM agent";
        $result_tmp = $this->_DB->fetchTable($query, true);

        if($result_tmp==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        $result = array();

        foreach($result_tmp as $tmp){
            $result[$tmp['type'].'/'.$tmp['number']] = $tmp['name'];
        }

        return $result;
    }

    // Статистика кампании
    function statCampaign($typeCampaign, $idCampaign){

        if($typeCampaign == 'outgoing'){
            $sql = 'SELECT COUNT(*) AS n, status FROM calls WHERE id_campaign = ? AND date_init >= CURDATE() GROUP BY status';
            $calls['status'] = array(
                'Pending'   =>  0,  // Llamada no ha sido realizada todavía (Вызов не было сделано еще)
                'Placing'   =>  0,  // Originate realizado, no se recibe OriginateResponse (Происходят сделано, происходят ответ получен)
                'Ringing'   =>  0,  // Se recibió OriginateResponse, no entra a cola (Происходят ответа, полученного, не входит очереди)
                'OnQueue'   =>  0,  // Entró a cola, no se asigna a agente todavía (Вступил хвост еще не назначен агентом)
                'Success'   =>  0,  // Conectada y asignada a un agente (Подключение и назначен агентом)
                'OnHold'    =>  0,  // Llamada fue puesta en espera por agente (Вызов был приостановлен агентом)
                'Failure'   =>  0,  // No se puede conectar llamada (Не удается подключиться вызов)
                'ShortCall' =>  0,  // Llamada conectada pero duración es muy corta (Позвоните подключен, но продолжительность коротка)
                'NoAnswer'  =>  0,  // Llamada estaba Ringing pero no entró a cola (Звон звонок был введен, но нет хвоста)
                'Abandoned' =>  0,  // Llamada estaba OnQueue pero no habían agentes (На очереди позвонили, но не было агентов)
            );
        }

        if($typeCampaign == 'incoming'){
            $sql = 'SELECT COUNT(*) AS n, status FROM call_entry WHERE id_campaign = ? AND (datetime_init >= CURDATE() OR (datetime_init IS NULL AND datetime_end >= CURDATE())) GROUP BY status';
            $calls['status'] = array(
                'terminada'  =>  0,
                'abandonada' =>  0,
                );
        }

        $recordset = $this->_DB->fetchTable($sql, TRUE, array($idCampaign));
        if (!is_array($recordset)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }

        foreach ($recordset as $callsStatus) {
            if (is_null($callsStatus['status']))
                $calls['status']['Pending'] = $callsStatus['n'];
            else $calls['status'][$callsStatus['status']] = $callsStatus['n'];
        }

        return $calls;
    }
}
