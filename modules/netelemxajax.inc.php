<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

function validateManagementUrl($params, $update = false) {
	global $DB;

	$error = NULL;

	if (!strlen($params['url']))
		$error['url'] = trans('Management URL cannot be empty!');
	elseif (strlen($params['url']) < 10)
		$error['url'] = trans('Management URL is too short!');

	return $error;
}

function getManagementUrls($formdata = NULL) {
	global $SMARTY, $DB;

	$result = new xajaxResponse();

	$netelemid = intval($_GET['id']);

	$mgmurls = NULL;
	$mgmurls = $DB->GetAll('SELECT id, url, comment FROM managementurls WHERE netelemid = ? ORDER BY id', array($netelemid));
	$SMARTY->assign('mgmurls', $mgmurls);
	if (isset($formdata['error']))
		$SMARTY->assign('error', $formdata['error']);
	$SMARTY->assign('formdata', $formdata);
	$mgmurllist = $SMARTY->fetch('managementurl/managementurllist.html');

	$result->assign('managementurltable', 'innerHTML', $mgmurllist);

	return $result;
}

function addManagementUrl($params) {
	global $DB, $SYSLOG, $SYSLOG_RESOURCE_KEYS;

	$result = new xajaxResponse();

	$error = validateManagementUrl($params);

	$params['error'] = $error;

	$netelemid = intval($_GET['id']);

	if (!$error) {
		if (!preg_match('/^[[:alnum:]]+:\/\/.+/i', $params['url']))
			$params['url'] = 'http://' . $params['url'];

		$args = array(
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netelemid,
			'url' => $params['url'],
			'comment' => $params['comment'],
		);
		$DB->Execute('INSERT INTO managementurls (netelemid, url, comment) VALUES (?, ?, ?)', array_values($args));
		if ($SYSLOG) {
			$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MGMTURL]] = $DB->GetLastInsertID('managementurls');
			$SYSLOG->AddMessage(SYSLOG_RES_MGMTURL, SYSLOG_OPER_ADD, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MGMTURL], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));
		}
		$params = NULL;
	}

	$result->call('xajax_getManagementUrls', $params);
	$result->assign('managementurladdlink', 'disabled', false);

	return $result;
}

function delManagementUrl($id) {
	global $DB, $SYSLOG, $SYSLOG_RESOURCE_KEYS;

	$result = new xajaxResponse();

	$netelemid = intval($_GET['id']);
	$id = intval($id);

	$res = $DB->Execute('DELETE FROM managementurls WHERE id = ?', array($id));
	if ($res && $SYSLOG) {
		$args = array(
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MGMTURL] => $id,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netelemid,
		);
		$SYSLOG->AddMessage(SYSLOG_RES_MGMTURL, SYSLOG_OPER_DELETE, $args, array_keys($args));
	}
	$result->call('xajax_getManagementUrls', $netelemid);
	$result->assign('managementurltable', 'disabled', false);

	return $result;
}

function updateManagementUrl($urlid, $params) {
	global $DB, $SYSLOG, $SYSLOG_RESOURCE_KEYS;

	$result = new xajaxResponse();

	$urlid = intval($urlid);
	$netelemid = intval($_GET['id']);

	$res = validateManagementUrl($params, true);

	$error = array();
	foreach ($res as $key => $val)
		$error[$key . '_edit_' . $urlid] = $val;
	$params['error'] = $error;

	if (!$error) {
		if (!preg_match('/^[[:alnum:]]+:\/\/.+/i', $params['url']))
			$params['url'] = 'http://' . $params['url'];

		$args = array(
			'url' => $params['url'],
			'comment' => $params['comment'],
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MGMTURL] => $urlid,
		);
		$DB->Execute('UPDATE managementurls SET url = ?, comment = ? WHERE id = ?', array_values($args));
		if ($SYSLOG) {
			$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]] = $netelemid;
			$SYSLOG->AddMessage(SYSLOG_RES_MGMTURL, SYSLOG_OPER_UPDATE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_MGMTURL], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));
		}
		$params = NULL;
	}

	$result->call('xajax_getManagementUrls', $params);
	$result->assign('managementurltable', 'disabled', false);

	return $result;
}

function validateRadioSector($params, $update = false) {
	global $DB;

	$error = NULL;

	if (!strlen($params['name']))
		$error['name'] = trans('Radio sector name cannot be empty!');
	elseif (strlen($params['name']) > 63)
		$error['name'] = trans('Radio sector name is too long!');
	elseif (!preg_match('/^[a-z0-9_\-]+$/i', $params['name']))
		$error['name'] = trans('Radio sector name contains invalid characters!');

	if (!strlen($params['azimuth']))
		$error['azimuth'] = trans('Radio sector azimuth cannot be empty!');
	elseif (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $params['azimuth']))
		$error['azimuth'] = trans('Radio sector azimuth has invalid format!');
	elseif ($params['azimuth'] >= 360)
		$error['azimuth'] = trans('Radio sector azimuth should be less than 360 degrees!');

	if (!strlen($params['width']))
		$error['width'] = trans('Radio sector angular width cannot be empty!');
	elseif (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $params['width']))
		$error['width'] = trans('Radio sector angular width has invalid format!');
	elseif ($params['width'] > 360)
		$error['width'] = trans('Radio sector angular width should be less than 360 degrees!');

	if (!strlen($params['altitude']))
		$error['altitude'] = trans('Radio sector altitude cannot be empty!');
	elseif (!preg_match('/^[0-9]+$/', $params['altitude']))
		$error['altitude'] = trans('Radio sector altitude has invalid format!');

	if (!strlen($params['rsrange']))
		$error['rsrange'] = trans('Radio sector range cannot be empty!');
	elseif (!preg_match('/^[0-9]+$/', $params['rsrange']))
		$error['rsrange'] = trans('Radio sector range has invalid format!');

	if (strlen($params['license']) > 63)
		$error['license'] = trans('Radio sector license number is too long!');

	if (strlen($params['frequency']) && !preg_match('/^[0-9]{1,3}(\.[0-9]{1,5})?$/', $params['frequency']))
		$error['frequency'] = trans('Radio sector frequency has invalid format!');

	if (strlen($params['frequency2'])) {
		if (!strlen($params['frequency']))
			$error['frequency2'] = trans('Radio sector second frequency should be also empty if first frequency is empty!');
		elseif (!preg_match('/^[0-9]{1,3}(\.[0-9]{1,5})?$/', $params['frequency2']))
			$error['frequency2'] = trans('Radio sector frequency has invalid format!');
	}

	if (strlen($params['bandwidth']) && !preg_match('/^[0-9]{1,4}?$/', $params['bandwidth']))
		$error['bandwidth'] = trans('Radio sector bandwidth has invalid format!');

	return $error;
}

function getRadioSectors($formdata = NULL, $result = NULL) {
	global $SMARTY, $DB;

	if (! $result)
		$result = new xajaxResponse();

	$netelemid = intval($_GET['id']);

	$radiosectors = $DB->GetAll('SELECT s.*, (CASE WHEN n.computers IS NULL THEN 0 ELSE n.computers END) AS computers,
		((CASE WHEN l1.elements IS NULL THEN 0 ELSE l1.elements END)
		+ (CASE WHEN l2.elements IS NULL THEN 0 ELSE l2.elements END)) AS elements
		FROM netradiosectors s
		LEFT JOIN (
			SELECT linkradiosector AS rs, COUNT(*) AS computers
			FROM nodes n WHERE n.ownerid > 0 AND linkradiosector IS NOT NULL
			GROUP BY rs
		) n ON n.rs = s.id
		LEFT JOIN (
			SELECT srcradiosector, COUNT(*) AS elements FROM netlinks GROUP BY srcradiosector
		) l1 ON l1.srcradiosector = s.id
		LEFT JOIN (
			SELECT dstradiosector, COUNT(*) AS elements FROM netlinks GROUP BY dstradiosector
		) l2 ON l2.dstradiosector = s.id
		WHERE s.netelem = ?
		ORDER BY s.name', array($netelemid));
	foreach ($radiosectors as $rsidx => $radiosector)
		if (!empty($radiosector['bandwidth']))
			$radiosectors[$rsidx]['bandwidth'] *= 1000;
	$SMARTY->assign('radiosectors', $radiosectors);
	if (isset($formdata['error']))
		$SMARTY->assign('error', $formdata['error']);
	$SMARTY->assign('formdata', $formdata);
	$radiosectorlist = $SMARTY->fetch('netelem/radiosectorlist.html');

	$result->assign('radiosectortable', 'innerHTML', $radiosectorlist);

	return $result;
}

function addRadioSector($params) {
	global $DB, $SYSLOG, $SYSLOG_RESOURCE_KEYS;

	$result = new xajaxResponse();

	$netelemid = intval($_GET['id']);

	$error = validateRadioSector($params);

	$params['error'] = $error;

	if (!$error) {
		$args = array(
			'name' => $params['name'],
			'azimuth' => $params['azimuth'],
			'width' => $params['width'],
			'altitude' => $params['altitude'],
			'rsrange' => $params['rsrange'],
			'license' => (strlen($params['license']) ? $params['license'] : null),
			'technology' => intval($params['technology']),
			'frequency' => (strlen($params['frequency']) ? $params['frequency'] : null),
			'frequency2' => (strlen($params['frequency2']) ? $params['frequency2'] : null),
			'bandwidth' => (strlen($params['bandwidth']) ? str_replace(',', '.', $params['bandwidth'] / 1000) : null),
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netelemid,
		);
		$DB->Execute('INSERT INTO netradiosectors (name, azimuth, width, altitude, rsrange, license, technology,
			frequency, frequency2, bandwidth, netelem)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array_values($args));
		if ($SYSLOG) {
			$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR]] = $DB->GetLastInsertID('netradiosectors');
			$SYSLOG->AddMessage(SYSLOG_RES_RADIOSECTOR, SYSLOG_OPER_ADD, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));
		}
		$params = NULL;
	}
	$result = getRadioSectors($params, $result);
	//$result->call('xajax_getRadioSectors', $params);
	$result->assign('add_new_radiosector_button', 'disabled', false);
	$result->assign('cancel_new_radiosector_button', 'disabled', false);

	return $result;
}

function delRadioSector($id) {
	global $DB, $SYSLOG, $SYSLOG_RESOURCE_KEYS;

	$result = new xajaxResponse();

	$netelemid = intval($_GET['id']);
	$id = intval($id);

	$res = $DB->Execute('DELETE FROM netradiosectors WHERE id = ?', array($id));
	if ($res && $SYSLOG) {
		$args = array(
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR] => $id,
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV] => $netelemid,
		);
		$SYSLOG->AddMessage(SYSLOG_RES_RADIOSECTOR, SYSLOG_OPER_DELETE, $args, array_keys($args));
	}
	$result->call('xajax_getRadioSectors', NULL);
	$result->assign('radiosectortable', 'disabled', false);

	return $result;
}

function updateRadioSector($rsid, $params) {
	global $DB;

	$result = new xajaxResponse();

	$rsid = intval($rsid);
	$netelemid = intval($_GET['id']);

	$res = validateRadioSector($params, true);
	$error = array();
	foreach ($res as $key => $val)
		$error[$key . '_edit_' . $rsid] = $val;
	$params['error'] = $error;

	if (!$error) {
		$args = array(
			'name' => $params['name'],
			'azimuth' => $params['azimuth'],
			'width' => $params['width'],
			'altitude' => $params['altitude'],
			'rsrange' => $params['rsrange'],
			'license' => (strlen($params['license']) ? $params['license'] : null),
			'technology' => intval($params['technology']),
			'frequency' => (strlen($params['frequency']) ? $params['frequency'] : null),
			'frequency2' => (strlen($params['frequency2']) ? $params['frequency2'] : null),
			'bandwidth' => (strlen($params['bandwidth']) ? str_replace(',', '.', $params['bandwidth'] / 1000) : null),
			$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR] => $rsid,
		);
		$DB->Execute('UPDATE netradiosectors SET name = ?, azimuth = ?, width = ?, altitude = ?,
			rsrange = ?, license = ?, technology = ?,
			frequency = ?, frequency2 = ?, bandwidth = ? WHERE id = ?', array_values($args));
		if ($SYSLOG) {
			$args[$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]] = $netelemid;
			$SYSLOG->AddMessage(SYSLOG_RES_RADIOSECTOR, SYSLOG_OPER_UPDATE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_RADIOSECTOR], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_NETDEV]));
		}
		$params = NULL;
	}

	$result->call('xajax_getRadioSectors', $params);

	return $result;
}

function getRadioSectorsForNetElem($callback_name, $elemid, $technology = 0) {
	global $DB;

	$result = new xajaxResponse();

	if (!in_array($callback_name, array('radio_sectors_received_for_srcnetelem', 'radio_sectors_received_for_dstnetelem',
		'radio_sectors_received_for_node')))
		return $result;

	$technology = intval($technology);
	$radiosectors = $DB->GetAll('SELECT id, name FROM netradiosectors WHERE netelem = ?'
		. ($technology ? ' AND (technology = ' . $technology . ' OR technology = 0)' : '')
		. ' ORDER BY name', array(intval($elemid)));
	$result->call($callback_name, $radiosectors);

	return $result;
}


function changeNetElementType($type,$clearports) {
	global $DB;
	$res = new xajaxResponse();
	$res->assign('elem_main','style.display','table-row-group');			
	$res->assign('elem_type_active','style.display', 'none');
	$res->assign('elem_type_passive','style.display', 'none');
	$res->assign('elem_type_cable','style.display', 'none');
	$res->assign('elem_type_splitter','style.display', 'none');
	$res->assign('elem_type_multiplexer','style.display', 'none');
	$res->assign('elem_type_computer','style.display', 'none');
	$res->assign('elem_ports','style.display', 'none');
	$res->assign('model', 'innerHTML', '');
	$res->assign('producer', 'innerHTML', '');
	if($clearports)$res->assign('porttable', 'innerHTML', '');
		  $q="SELECT distinct(p.id), p.name FROM netdeviceproducers p 
		      LEFT JOIN netdevicemodels m ON p.id=m.netdeviceproducerid
		      WHERE m.type=".$type;
		  $producers = $DB->getAll($q);
		  $res->script("var d=document.getElementById('producer'); d.options.length=0;");
		  $res->script("var d=document.getElementById('producer'); d.options[d.options.length]=new Option('Select','-1');");
		  foreach($producers as $p){
		      $res->script("var d=document.getElementById('producer'); d.options[d.options.length]=new Option('".$p['name']."','".$p['id']."');");
		  }
	switch($type){
	case '0':
		$res->assign('elem_type_active','style.display', 'table-row-group');
		$res->assign('elem_ports','style.display', 'table-row-group');
		break;
	case '1':
		$res->assign('elem_type_passive','style.display', 'table-row-group');
		$res->assign('elem_ports','style.display', 'table-row-group');
		break;
	case '2':
		$res->call('xajax_changeWireType',1,0,0);
		$res->assign('elem_type_cable','style.display', 'table-row-group');
		break;
	case '3':
		$res->assign('elem_type_splitter','style.display', 'table-row-group');
		break;
	case '4':
		$res->assign('elem_type_multiplexer','style.display', 'table-row-group');
		break;
	case '99':
		$res->assign('elem_type_computer','style.display', 'table-row-group');
		break;
	case 'default':
		#$res->assign('elem_main','style.display','none');		
	}
	return $res;
}

function addports($devtype){
	global $NETPORTTYPES, $NETCONNECTORS, $NETTECHNOLOGIES;
	$res = new xajaxResponse();
	$index=substr(microtime(),2,8);
  switch($devtype){
    case 0://active
    case 99: //client computer
  	$types_allowed=array(1,2,3,4,100,200);
	$connectors_allowed=array(1,2,3,4,5,6,50,51, 100,101,102,103,104,151, 201,202,203,210,211,212,213,220,221,222,223,230,231,232,233,240,241,242,243);
	$tech_allowed=array(1,2,3,4,5,6,7,8,9,10,11,12,50,51,52,70,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114, 200,201,202,203,204,205,206,207,208,209,210,211,212,213);
    break;
    case 1://passive
	$types_allowed=array(1,2,200,300);
  	$connectors_allowed=array(1,2,3,4,5,6,50,51, 201,202,203,210,211,212,213,220,221,222,223,230,231,232,233,240,241,242,243,999);
  	$tech_allowed=array('null');
    break;
  }
	foreach($types_allowed as $t){
		$toptions.='<option value="'.$t.'">'.$NETPORTTYPES[$t].'</option>';		
	}
	foreach($connectors_allowed as $c){
		$coptions.='<option value="'.$c.'">'.$NETCONNECTORS[$c].'</option>';
	}
	foreach($tech_allowed as $tn){
		if($tn=='null')$techoptions.='<option value="null">N/A</option>';
		else $techoptions.='<option value="'.$tn.'">'.$NETTECHNOLOGIES[$tn]['name'].'</option>';
	}
	$res->append('porttable','innerHTML','<tr><td>'.trans('Label:').'<input name="netports['.$index.'][label]">
		      <select name="netports['.$index.'][netporttype]" id="ptype'.$index.'" onchange="xajax_getConnectorOptionsByPortType(this.value, \'conn'.$index.'\')">'.$toptions.'</select>
		      '.trans("Technology").':<select name="netports['.$index.'][technology]" id="ptech'.$index.'">'.$techoptions.'</select>
		      '.trans("Connector").':<select name="netports['.$index.'][netconnector]" id="pconn'.$index.'">'.$coptions.'</select>
		      <IMG src="img/add.gif" alt="" title="{trans("Clone")}" onclick="clone(this);">&nbsp;
		      <IMG src="img/delete.gif" alt="" title="'.trans("Delete").'" onclick="remports(this);">
		      </td></tr>');

	
	
	return $res;
}

function updateTechnologyAndConnector($index, $devtype, $medium){
	global $NETTECHNOLOGIES, $NETCONNECTORS;
	$res= new xajaxResponse();
error_log('i:'.$index.' typ:'.$devtype.' med:'.$medium);
  switch($devtype){
    case 0://active
    case 99: //client computer
	switch($medium){
	    case 1:
	      $connectors_allowed=array(1,2,3,4,5,6,50,51);
	      $tech_allowed=array(1,2,3,4,5,6,7,8,9,10,11,12,50,51,52,70);
	    break;
	    case 2:
	      $connectors_allowed=array(3,6);
	      $tech_allowed=array(1,2,3,4,5,10,11,12,70);
	    break;
	    case 3:
	    case 4:
	      $connectors_allowed=array(1,2,200,201,202,203,210,211,212,213,220,221,222,223,230,231,232,233,240,241,242,243);
	      $tech_allowed=array(6,7,8,9,10,200,201,202,203,204,205,206,207,208,209,210,211,212,213);
	    break;
	    case 100:
	      $connectors_allowed=array(100,101,102,103,104,151);
	      $tech_allowed=array(100,101,102,103,104,105,106,107,108,109,110,111,112,113,114);
	    break;
	    case 200:
	      $connectors_allowed=array(200,201,202,203,210,211,212,213,220,221,222,223,230,231,232,233,240,241,242,243);
	      $tech_allowed=array(200,201,202,203,204,205,206,207,208,209,210,211,212,213);
	    break;
	}
    break;
    case 1://passive
  	switch($medium){
	    case 1:
	      $connectors_allowed=array(1,2,3,4,5,6,50,51);
	      $tech_allowed=array('null');
	    break;
	    case 2:
	      $connectors_allowed=array(3,6);
	      $tech_allowed=array('null');
	    break;
	    case 3:
	    case 4:
	      $connectors_allowed=array(1,2,200,201,202,203,210,211,212,213,220,221,222,223,230,231,232,233,240,241,242,243,99);
	      $tech_allowed=array('null');
	    break;
	    case 200:
	      $connectors_allowed=array(200,201,202,203,210,211,212,213,220,221,222,223,230,231,232,233,240,241,242,243);
	      $tech_allowed=array('null');
	    break;
	    case 300:
	      $connectors_allowed=array(999);
	      $tech_allowed=array('null');
	    break;
	}
    break;
  }
	foreach($connectors_allowed as $c){
		$coptions.='<option value="'.$c.'">'.$NETCONNECTORS[$c].'</option>';
	}
	foreach($tech_allowed as $tn){
		if($tn=='null')$techoptions.='<option value="null">N/A</option>';
		else $techoptions.='<option value="'.$tn.'">'.$NETTECHNOLOGIES[$tn]['name'].'</option>';
	}
	$res->assign('ptech'.$index, 'innerHTML', $techoptions);
	$res->assign('pconn'.$index, 'innerHTML', $coptions);
	return $res;
}

function updateConnectorOnMediumAndTechnology($medium, $tech){
      global $NETTECHNOLOGIES, $NETCONNECTORS;
      $res= new xajaxResponse();
 /*     switch($tech){
	case 1:	
	case 2:	
	case 3:	
	case 12:
	//6
	break;
	
	case 4:
	case 5:
	case 10:
	case 11:
	//3
	break;

	case 6:
	case 7:
	case 8:
	case 9:
	  if($tech==6 || $tech==7) //connector 1 and 2
	  //1
	break;
	
	case 50:
	case 51:
	case 52:
	  //8
	break;
	case 70:
	//6
	break;
	case 100:
	break;
	case 101:
	break;
	case 102:
	break;
	case 103:
	break;
	case 104:
	break;
	case 105:
	break;
	case 106:
	break;
	case 107:
	break;
	case 108:
	break;
	case 109:
	break;
	case 110:
	break;
	case 111:
	break;
	case 112:
	break;
	case 113:
	break;
	case 114:
	break;
	case 200:
	case 201:
	case 202:
	case 203:
	case 204:
	case 205:
	case 206:
	case 207:
	case 208:
	case 209:
	case 210:
	case 211:
	case 212:
	case 213:
	//201,202,203,210,211,212,213,220,221,222,223,230,231,232,233,240,241,242,243
	break;

      }
*/      
      return $res;
}

function setPortsForModel($modelid){
	global $NETTECHNOLOGIES, $NETPORTTYPES, $NETCONNECTORS, $DB;
	$res= new xajaxResponse();
	$model = $DB->GetRow("SELECT m.name model , p.name producer FROM netdevicemodels m LEFT JOIN netdeviceproducers p ON p.id=m.netdeviceproducerid WHERE m.id=".$modelid." LIMIT 0,1");
error_log("model:".print_r($model, true));
	$ports = $DB->getAll("SELECT id, label, connector, port_type, technology FROM netdeviceschema WHERE model=".$modelid." ORDER by connector, label");
	$res->assign('window.top.porttable','innerHTML','duuupa');
	foreach($ports as $p){
	  $list.='<tr>
			  <td class="nobr" colspan="3">
			    '.trans("Label:").'<input type=text name="netports['.$p['id'].'][label]" value="'.$p['label'].'">
			    '.trans("Type:").'<select name="netports['.$p['id'].'][netporttype]" id="ptype'.$p['id'].'" onchange="xajax_getConnectorOptionsByPortType(this.value, \'pconn'.$p['id'].'\')">';		    
	foreach( $NETPORTTYPES as $key=>$val){
		$list.='<option value="'.$key.'"';
			if( $key==$p['port_type']) $list.='selected';
		$list.='>'.$val.'</option>';
	}
		$list.='</select>'.trans("Technology")
		  .': <select name="netports['.$p['id'].'][nettechnology]" id="ptech'.$p['id'].'" onchange="xajax_getConnectorOptionsByMediumAndDevType(this.value, document.getElementById(\'medium'.$p['id'].'\').value, \'pconn'.$p['id'].'\')">';
	foreach( $NETTECHNOLOGIES as $key=>$val){
		$list.='<option value="'.$key.'"';
			if( $key==$p['technology']) $list.='selected';
		$list.='>'.$val['name'].'</option>';
	}
		 $list.='</select>'.trans("connector").':<select name="netports['.$p['id'].'][netconnector]" id="pconn'.$p['id'].'">';
	foreach( $NETCONNECTORS as $key=>$val){
		$list.='<option value="'.$key.'"';
			if( $key==$p['connector']) $list.='selected';
		$list.='>'.$val.'</option>';
	}
		$list.=	    '</select>
			    <IMG src="img/clone.gif" alt="" title="{trans("Clone")}" onclick="clone(this);">&nbsp;
			    <IMG src="img/delete.gif" alt="" title="{trans("Delete")}" onclick="remports(this);">
			  </td>
		</tr>';
			
	}
	$res->assign('porttable','innerHTML',$list);
	$res->assign('producer', 'value', $model['producer']);
	$res->assign('model', 'value', $model['model']);
	return $res;
}

function changeWireType($type,$tschemaid,$ttype) {
	global $COPPERCOLORSCHEMAS,$FIBEROPTICCOLORSCHEMAS,$NETWIRETYPES;
	$res = new xajaxResponse();
	if ($type<100) {
		$colorschema=$COPPERCOLORSCHEMAS;
		if ($type<50) {
			$start=0;$end=50;
		} else {
			$start=50;$end=100;
		}
	} else {
		$colorschema=$FIBEROPTICCOLORSCHEMAS;
		$start=200;$end=300;
	}
	$cselect='';
	foreach ($colorschema AS $id => $schema) {
		$cselect.='<OPTION VALUE="'.$id.'"';
		if ($id==$tschemaid) $cselect.=' SELECTED';		
		$cselect.='>'.$schema['label'].'</OPTION>';
	}
	$tselect='';
	foreach ($NETWIRETYPES AS $id => $type) {
		if ($id>$start AND $id<=$end) {
			$tselect.='<OPTION VALUE="'.$id.'"';
			if ($id==$ttype) $tselect.=' SELECTED';
			$tselect.='>'.$type.'</OPTION>';
		}
	}
	$res->assign('colorschema','innerHTML',$cselect);
	$res->assign('wiretype','innerHTML',$tselect);
	return $res;
}

global $LMS,$SMARTY;
$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array(
	'getManagementUrls','addManagementUrl', 'delManagementUrl', 'updateManagementUrl',
	'getRadioSectors', 'addRadioSector', 'delRadioSector', 'updateRadioSector',
	'getRadioSectorsForNetElem','updateTechnologyAndConnector',
	'changeNetElementType','changeWireType','setPortsForModel','addports',
));
$SMARTY->assign('xajax', $LMS->RunXajax());

?>