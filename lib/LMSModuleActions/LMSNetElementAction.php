<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2013 LMS Developers
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

/**
 * LMSNetElemAction
 *
 * @author Jaroslaw Dziubek <jaroslaw.dziubek@perfect.net.pl> 
 */
class LMSNetElemAction extends LMSModuleAction 
{
	public function Route() {
		if(!isset($_GET['action'])) $_GET['action']='';
		switch ($_GET['action']) {
			case 'list':
				$this->_list();
				break;
			case 'add':
				$this->_add();
				break;
			case 'edit':
				$this->_edit();
				break;
			case 'info':
				$this->_info();
				break;
			case 'models':
				$this->_models();
				break;
			default:
				$this->_list();
				break;
		}
	}

	function _list() {
		$layout['pagetitle'] = trans('Network Elements');

		if(!isset($_GET['o']))
			$this->session->restore('nelo', $o);
		else
			$o = $_GET['o'];
		$this->session->save('nelo', $o);

		if(!isset($_GET['t']))
			$this->session->restore('neft', $t);
		else
			$t = $_GET['t'];
		$this->session->save('neft', $t);

		if(!isset($_GET['s']))
			$this->session->restore('nefs', $s);
		else
			$s = $_GET['s'];
		$this->session->save('nefs', $s);

		if(!isset($_GET['p']))
			$this->session->restore('nefp', $p);
		else
			$p = $_GET['p'];
		$this->session->save('nefp', $p);

		if(!isset($_GET['n']))
			$this->session->restore('nefn', $n);
		else
			$n = $_GET['n'];
		$this->session->save('nefn', $n);

		if(!isset($_GET['producer']))
			$this->session->restore('nefproducer', $producer);
		else
			$producer = $_GET['producer'];
		$this->session->save('nefproducer', $producer);

		if(!isset($_GET['model']))
			$this->session->restore('nefmodel', $model);
		else
			$model = $_GET['model'];
		$this->session->save('nefmodel', $model);

		if (empty($model))
			$model = -1;
		if (empty($producer))
			$producer = -1;

		$producers = $this->db->GetCol("SELECT DISTINCT UPPER(TRIM(producer)) AS producer FROM netelements WHERE producer <> '' ORDER BY producer");
		$models = $this->db->GetCol("SELECT DISTINCT UPPER(TRIM(model)) AS model FROM netelements WHERE model <> ''"
			. ($producer != '-1' ? " AND UPPER(TRIM(producer)) = " . $this->db->Escape($producer == '-2' ? '' : $producer) : '') . " ORDER BY model");
		if (!preg_match('/^-[0-9]+$/', $model) && !in_array($model, $models)) {
			$this->session->save('nefmodel', '-1');
			$this->session->redirect('?' . preg_replace('/&model=[^&]+/', '', $_SERVER['QUERY_STRING']));
		}
		if (!preg_match('/^-[0-9]+$/', $producer) && !in_array($producer, $producers)) {
			$this->session->save('nefproducer', '-1');
			$this->session->redirect('?' . preg_replace('/&producer=[^&]+/', '', $_SERVER['QUERY_STRING']));
		}

		$search = array(
			'type' => $t,
			'status' => $s,
			'project' => $p,
			'netnode' => $n,
			'producer' => $producer,
			'model' => $model,
		);
		$netelemlist = $this->lms->GetNetElemList($o, $search);
		$listdata['total'] = $netelemlist['total'];
		$listdata['order'] = $netelemlist['order'];
		$listdata['direction'] = $netelemlist['direction'];
		$listdata['type'] = $t;
		$listdata['status'] = $s;
		$listdata['invprojectid'] = $p;
		$listdata['netnode'] = $n;
		$listdata['producer'] = $producer;
		$listdata['model'] = $model;
		unset($netelemlist['total']);
		unset($netelemlist['order']);
		unset($netelemlist['direction']);

		if(!isset($_GET['page']))
			$this->session->restore('nelp', $_GET['page']);
			
		$page = (! $_GET['page'] ? 1 : $_GET['page']);
		$pagelimit = ConfigHelper::getConfig('phpui.nodelist_pagelimit', $listdata['total']);
		$start = ($page - 1) * $pagelimit;

		$this->session->save('nelp', $page);

		$this->session->save('backto', $_SERVER['QUERY_STRING']);

		$this->smarty->assign('page',$page);
		$this->smarty->assign('pagelimit',$pagelimit);
		$this->smarty->assign('start',$start);
		$this->smarty->assign('netelemlist',$netelemlist);
		$this->smarty->assign('listdata',$listdata);
		$this->smarty->assign('netnodes', $this->db->GetAll("SELECT id, name FROM netnodes ORDER BY name"));
		$this->smarty->assign('NNprojects', $this->db->GetAll("SELECT * FROM invprojects WHERE type<>? ORDER BY name",
			array(INV_PROJECT_SYSTEM)));
		$this->smarty->assign('producers', $producers);
		$this->smarty->assign('models', $models);
		$this->smarty->display('netelements/list.html');
	}

	function _add() {
		if(isset($_POST['netelem']))
		{
			$netelemdata = $_POST['netelem'];

			if($netelemdata['ports'] == '')
				$netelemdata['ports'] = 0;
			else
				$netelemdata['ports'] = intval($netelemdata['ports']);

			if(empty($netelemdata['clients']))
				$netelemdata['clients'] = 0;
			else
				$netelemdata['clients'] = intval($netelemdata['clients']);

			if($netelemdata['name'] == '')
				$error['name'] = trans('Element name is required!');
			elseif (strlen($netelemdata['name']) > 60)
				$error['name'] = trans('Specified name is too long (max. $a characters)!', '60');

			$netelemdata['purchasetime'] = 0;
			if($netelemdata['purchasedate'] != '') 
			{
				// date format 'yyyy/mm/dd'
				if(!preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $netelemdata['purchasedate']))
				{
					$error['purchasedate'] = trans('Invalid date format!');
				}
				else
				{
					$date = explode('/', $netelemdata['purchasedate']);
					if(checkdate($date[1], $date[2], (int)$date[0]))
					{
						$tmpdate = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
						if(mktime(0,0,0) < $tmpdate)
							$error['purchasedate'] = trans('Date from the future not allowed!');
						else
							$netelemdata['purchasetime'] = $tmpdate;
					}
					else
						$error['purchasedate'] = trans('Invalid date format!');
				}
			}

			if($netelemdata['guaranteeperiod'] != 0 && $netelemdata['purchasetime'] == NULL)
			{
				$error['purchasedate'] = trans('Purchase date cannot be empty when guarantee period is set!');
			}


			if ($netelemdata['invprojectid'] == '-1') { // nowy projekt
				if (!strlen(trim($netelemdata['projectname']))) {
				 $error['projectname'] = trans('Project name is required');
				}
				$l = $this->db->GetOne("SELECT * FROM invprojects WHERE name=? AND type<>?",
					array($netelemdata['projectname'], INV_PROJECT_SYSTEM));
				if (sizeof($l)>0) {
					$error['projectname'] = trans('Project with that name already exists');
				}
			}

		    if(!$error)
		    {
				if($netelemdata['guaranteeperiod'] == -1)
					$netelemdata['guaranteeperiod'] = NULL;

				if(!isset($netelemdata['shortname'])) $netelemdata['shortname'] = '';
			if(!isset($netelemdata['secret'])) $netelemdata['secret'] = '';
			if(!isset($netelemdata['community'])) $netelemdata['community'] = '';
			if(!isset($netelemdata['nastype'])) $netelemdata['nastype'] = 0;

			if (empty($netelemdata['teryt'])) {
			    $netelemdata['location_city'] = null;
			    $netelemdata['location_street'] = null;
			    $netelemdata['location_house'] = null;
			    $netelemdata['location_flat'] = null;
			}
			$ipi = $netelemdata['invprojectid'];
			if ($ipi == '-1') {
				$this->db->BeginTrans();
				$this->db->Execute("INSERT INTO invprojects (name, type) VALUES (?, ?)",
					array($netelemdata['projectname'], INV_PROJECT_REGULAR));
				$ipi = $this->db->GetLastInsertID('invprojects');
				$this->db->CommitTrans();
			} 
			if ($netelemdata['invprojectid'] == '-1' || intval($ipi)>0)
				$netelemdata['invprojectid'] = intval($ipi);
			else
				$netelemdata['invprojectid'] = NULL;
			if ($netelemdata['netnodeid']=="-1") {
				$netelemdata['netnodeid']=NULL;
			}
			else {
				/* dziedziczenie lokalizacji */
				$dev = $this->db->GetRow("SELECT * FROM netnodes WHERE id = ?", array($netelemdata['netnodeid']));
				if ($dev) {
					if (!strlen($netelemdata['location'])) {
						$netelemdata['location'] = $dev['location'];
						$netelemdata['location_city'] = $dev['location_city'];
						$netelemdata['location_street'] = $dev['location_street'];
						$netelemdata['location_house'] = $dev['location_house'];
						$netelemdata['location_flat'] = $dev['location_flat'];
					}
					if (!strlen($netelemdata['longitude']) || !strlen($netelemdata['longitude'])) {
						$netelemdata['longitude'] = $dev['longitude'];
						$netelemdata['latitude'] = $dev['latitude'];
					}
				}
			}

				$netelemid = $this->lms->NetElemAdd($netelemdata);

				$this->session->redirect('?m=netelement&action=info&id='.$netelemid);
		    }

			$this->smarty->assign('error', $error);
			$this->smarty->assign('netelem', $netelemdata);
		} elseif (isset($_GET['id'])) {
			$netelemdata = $this->lms->GetNetElem($_GET['id']);
			$netelemdata['name'] = trans('$a (clone)', $netelemdata['name']);
			$netelemdata['teryt'] = !empty($netelemdata['location_city']) && !empty($netelemdata['location_street']);
			$this->smarty->assign('netelem', $netelemdata);
		}

		$layout['pagetitle'] = trans('New Element');

		$this->smarty->assign('nastype', $this->lms->GetNAStypes());

		$nprojects = $this->db->GetAll("SELECT * FROM invprojects WHERE type<>? ORDER BY name", array(INV_PROJECT_SYSTEM));
		$this->smarty->assign('NNprojects',$nprojects);
		$netnodes = $this->db->GetAll("SELECT * FROM netnodes ORDER BY name");
		$this->smarty->assign('NNnodes',$netnodes);

		if (ConfigHelper::checkConfig('phpui.ewx_support'))
			$this->smarty->assign('channels', $this->db->GetAll('SELECT id, name FROM ewx_channels ORDER BY name'));

		switch ($netelemdata['type']) {
		case '0':
			$this->smarty->display('netelements/addactive.html');
			break;
		case '1':
			$this->smarty->display('netelements/addpassive.html');
			break;
		case '2':
			$this->smarty->display('netelements/addcable.html');
			break;
                case '3':
                        $this->smarty->display('netelements/addsplitter.html');
                        break;
                case '4':
                        $this->smarty->display('netelements/addmultiplexer.html');
                        break;
                case '99':
                        $this->smarty->display('netelements/addcomputer.html');
                        break;
		default:
                        $this->smarty->display('netelements/addchoose.html');
			break;
		}	
	}	

	function _edit() {

	} 

	function _info() {
		if (!$this->lms->NetElemExists($_GET['id'])) {
			$this->session->redirect('?m=netelement&action=list');
		}

		include(MODULES_DIR . '/netelemxajax.inc.php');

		if (! array_key_exists('xjxfun', $_POST)) {                  // xajax was called and handled by netelemxajax.inc.php
			$neteleminfo = $this->lms->GetNetElem($_GET['id']);
			$netelemconnected = $this->lms->GetNetElemConnectedNames($_GET['id']);
			$netcomplist = $this->lms->GetNetElemLinkedNodes($_GET['id']);
			$netelemlist = $this->lms->GetNotConnectedElements($_GET['id']);

			$nodelist = $this->lms->GetUnlinkedNodes();
			$netelemips = $this->lms->GetNetElemIPs($_GET['id']);

			$this->session->save('backto', $_SERVER['QUERY_STRING']);

			$layout['pagetitle'] = trans('Element Info: $a $b $c', $neteleminfo['name'], $neteleminfo['producer'], $neteleminfo['model']);

			$neteleminfo['id'] = $_GET['id'];

			if ($neteleminfo['netnodeid']) {
				$netnode = $this->db->GetRow("SELECT * FROM netnodes WHERE id=".$neteleminfo['netnodeid']);
				if ($netnode) {
					$neteleminfo['nodename'] = $netnode['name'];
				}
			}

			$neteleminfo['projectname'] = trans('none');
			if ($neteleminfo['invprojectid']) {
				$prj = $this->db->GetRow("SELECT * FROM invprojects WHERE id = ?", array($neteleminfo['invprojectid']));
				if ($prj) {
					if ($prj['type'] == INV_PROJECT_SYSTEM && intval($prj['id'])==1) {
						/* inherited */
						if ($netnode) {
							$prj = $this->db->GetRow("SELECT * FROM invprojects WHERE id=?",
								array($netnode['invprojectid']));
							if ($prj)
								$neteleminfo['projectname'] = trans('$a (from network node $b)', $prj['name'], $netnode['name']);
						}
					} else
						$neteleminfo['projectname'] = $prj['name'];
				}
			}
			$this->smarty->assign('neteleminfo', $neteleminfo);
			$this->smarty->assign('objectid', $neteleminfo['id']);
			$this->smarty->assign('restnetelemlist', $netelemlist);
			$this->smarty->assign('netelemips', $netelemips);
			$this->smarty->assign('nodelist', $nodelist);
			$this->smarty->assign('elemlinktype', $this->session->get('elemlinktype'));
			$this->smarty->assign('elemlinktechnology', $this->session->get('elemlinktechnology'));
			$this->smarty->assign('elemlinkspeed', $this->session->get('elemlinkspeed'));
			$this->smarty->assign('nodelinktype', $this->session->get('nodelinktype'));
			$this->smarty->assign('nodelinktechnology', $this->session->get('nodelinktechnology'));
			$this->smarty->assign('nodelinkspeed', $this->session->get('nodelinkspeed'));

			$hook_data = $this->lms->executeHook('neteleminfo_before_display',
				array(
					'netelemconnected' => $netelemconnected,
					'netcomplist' => $netcomplist,
					'smarty' => $this->smarty,
				)
			);
			$netelemconnected = $hook_data['netelemconnected'];
			$netcomplist = $hook_data['netcomplist'];
			$this->smarty->assign('netelemlist', $netelemconnected);
			$this->smarty->assign('netcomplist', $netcomplist);

			if ($neteleminfo['type']==0) {
				if (isset($_GET['ip'])) {
					$nodeipdata = $this->lms->GetNodeConnType($_GET['ip']);
					$netelemauthtype = array();
					$authtype = $nodeipdata;
					if ($authtype != 0) {
						$netelemauthtype['dhcp'] = ($authtype & 2);
						$netelemauthtype['eap'] = ($authtype & 4);
					}
					$this->smarty->assign('nodeipdata', $this->lms->GetNode($_GET['ip']));
					$this->smarty->assign('netelemauthtype', $netelemauthtype);
					$this->smarty->display('netelements/ipinfo.html');
				} else {
					$this->smarty->display('netelements/info.html');
				}
			}
		}
	}

	function _models() {

		include(MODULES_DIR . '/modelxajax.inc.php');

		$layout['pagetitle'] = trans("Network device producers and models");
		$listdata = $modellist = array();
		$producerlist = $this->db->GetAll('SELECT id, name FROM netdeviceproducers ORDER BY name ASC');


		if (!isset($_GET['p_id'])) 
			$this->session->restore('ndpid', $pid);
		else
			$pid = intval($_GET['p_id']);
		$this->session->save('ndpid', $pid);

		if (!isset($_GET['page']))
			$this->session->restore('ndlpage', $_GET['page']);

		if ($pid)
			$producerinfo = $this->db->GetRow('SELECT p.id , p.alternative_name FROM netdeviceproducers p WHERE p.id = ?', array($pid));
		else
			$producerinfo = array();

		$listdata['pid'] = $pid; // producer id

		$this->smarty->assign('NETPORTTYPES',$NETPORTTYPES);
		$this->smarty->assign('NETCONNECTORS',$NETCONNECTORS);

                $modellist = $this->lms->GetModelList($pid);

                $listdata['total'] = sizeof($modellist);

                $page = (!$_GET['page'] ? 1 : $_GET['page']);
                $pagelimit = ConfigHelper::getConfig('phpui.netdevmodel_pagelimit', $listdata['total']);
                $start = ($page - 1) * $pagelimit;

                $this->session->save('ndlpage',$page);

                $this->smarty->assign('xajax', $this->lms->RunXajax());
                $this->smarty->assign('listdata',$listdata);
                $this->smarty->assign('producerlist',$producerlist);
                $this->smarty->assign('modellist',$modellist);
                $this->smarty->assign('producerinfo',$producerinfo);
                $this->smarty->assign('pagelimit',$pagelimit);
                $this->smarty->assign('page',$page);
                $this->smarty->assign('start',$start);
                $this->smarty->display('netelements/models.html');
	}		

}