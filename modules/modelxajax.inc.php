<?php

function cancel_producer() {
	$obj = new xajaxResponse();

	$obj->assign("id_producer","value","");
	$obj->assign("id_producername","value","");
	$obj->assign("id_alternative_name","value","");
	$obj->script("removeClass(xajax.$('id_producername'),'alert');");
	$obj->script("xajax.$('div_produceredit').style.display='none';");

	return $obj;
}


function add_producer() {
	$obj = new xajaxResponse();

	$obj->script("xajax.$('div_produceredit').style.display='';");
	$obj->script("removeClass(xajax.$('id_producername'),'alert');");
	$obj->assign("id_action_name","innerHTML",trans('New producer'));
	$obj->assign("id_producer","value","");
	$obj->assign("id_producername","value","");
	$obj->assign("id_alternative_name","value","");
	$obj->script("xajax.$('id_producername').focus();");

	return $obj;
}

function edit_producer($id) {
	global $DB;
	$obj = new xajaxResponse();

	$producer = $DB->GetRow('SELECT * FROM netdeviceproducers WHERE id = ?',
		array($id));

	$obj->script("xajax.$('div_produceredit').style.display='';");
	$obj->script("removeClass(xajax.$('id_producername'),'alert');");
	$obj->assign("id_action_name","innerHTML", trans("Producer edit: $a", $producer['name']));

	$obj->assign("id_producer","value", $producer['id']);
	$obj->assign("id_producername","value", $producer['name']);
	$obj->assign("id_alternative_name","value", $producer['alternative_name']);
	$obj->script("xajax.$('id_producername').focus();");

	return $obj;
}

function save_producer($forms) {
	global $DB;
	$obj = new xajaxResponse();

	$form = $forms['produceredit'];
	$formid = $form['id'];
	$pid = $form['pid'];
	$error = false;

	$obj->script("removeClass(xajax.$('id_producername'),'alert');");

	if (empty($form['name'])) {
		$error = true;
		$obj->setEvent("id_producername","onmouseover", "popup('<span class=\\\"red bold\\\">" . trans("Producer name is required!") . "</span>')");
	}

	if (!$error) {
		if (!$form['id'])
			$error = ($DB->GetOne('SELECT COUNT(*) FROM netdeviceproducers WHERE name = ?',
				array(strtoupper($form['name']))) ? true : false);
		else
			$error = ($DB->GetOne('SELECT COUNT(*) FROM netdeviceproducers WHERE name = ? AND id <> ? ',
				array(strtoupper($form['name']), $form['id'])) ? true : false);

		if ($error)
			$obj->setEvent("id_producername","onmouseover", "popup('<span class=\\\"red bold\\\">" . trans("Producer already exists!") . "</span>')");
	}

	if ($error) {
		$obj->script("addClass(xajax.$('id_producername'),'alert');");
		$obj->script("xajax.$('id_producername').focus();");
	} else {
		if ($form['id']) {
			$DB->Execute('UPDATE netdeviceproducers SET name = ?, alternative_name = ? WHERE id = ?',
				array($form['name'],
					($form['alternative_name'] ? $form['alternative_name'] : NULL),
					$form['id']
				));
			$obj->script("xajax_cancel_producer();");
			$obj->script("self.location.href='?m=netelement&action=models&page=1&p_id=$formid';");
		} else {
			$DB->Execute('INSERT INTO netdeviceproducers (name, alternative_name) VALUES (?, ?)',
				array($form['name'],
					($form['alternative_name'] ? $form['alternative_name'] : NULL)
				));

			$obj->script("xajax_cancel_producer();");
			$obj->script("self.location.href='?m=netelement&action=models&page=1&p_id=" . $DB->getLastInsertId('netdeviceproducers') . "';");
		}
	}

	return $obj;
}

function delete_producer($id) {
	global $DB;
	$obj = new xajaxResponse();

	$DB->Execute('DELETE FROM netdeviceproducers WHERE id = ?', array($id));

	$obj->script("self.location.href='?m=netelement&action=models&page=1&p_id=';");

	return $obj;
}


function cancel_model() {
	$obj = new xajaxResponse();

	$obj->assign("id_model","value","");
	$obj->assign("id_modelname","value","");
	$obj->assign("id_model_alternative_name","value","");
	$obj->script("removeClass(xajax.$('id_model_name'),'alert');");
	$obj->script("xajax.$('div_modeledit').style.display='none';");

	return $obj;
}

function add_model() {
	$obj = new xajaxResponse();

	$obj->script("xajax.$('div_modeledit').style.display='';");
	$obj->script("removeClass(xajax.$('id_model_name'),'alert');");
	$obj->assign("id_model_action_name","innerHTML",trans("New model"));
	$obj->assign("id_model","value","");
	$obj->assign("id_model_name","value","");
	$obj->assign("id_model_alternative_name","value","");
	$obj->script("xajax.$('id_model_name').focus();");

	return $obj;
}

function edit_model($id) {
	global $DB;
	$obj = new xajaxResponse();

	$model = $DB->GetRow('SELECT * FROM netdevicemodels WHERE id = ?', array($id));

	$obj->script("xajax.$('div_modeledit').style.display='';");
	$obj->script("removeClass(xajax.$('id_model_name'),'alert');");
	$obj->assign("id_model_action_name","innerHTML", trans('Model edit: $a', $model['name']));

	$obj->assign("id_model","value", $model['id']);
	$obj->assign("id_model_name","value", $model['name']);
	$obj->assign("id_model_alternative_name","value", $model['alternative_name']);
	$obj->script("xajax.$('id_model_name').focus();");

	return $obj;
}

function save_model($forms) {
	 global $DB;
	$obj = new xajaxResponse();

	$form = $forms['modeledit'];
	$formid = intval($form['id']);
	$pid = intval($form['pid']);
	$error = false;

	$obj->script("removeClass(xajax.$('id_model_name'),'alert');");

	if (empty($form['name'])) {
		$error = true;
		$obj->setEvent("id_model_name","onmouseover", "popup('<span class=\\\"red bold\\\">" . trans("Model name is required!") . "</span>')");
	}

	if (!$error) {
		if (!$form['id'])
			$error = ($DB->GetOne('SELECT COUNT(*) FROM netdevicemodels WHERE netdeviceproducerid = ? AND UPPER(name) = ? ',
				array($pid, strtoupper($form['name']))) ? true : false);
		else
			$error = ($DB->GetOne('SELECT COUNT(*) FROM netdevicemodels WHERE id <> ? AND netdeviceproducerid = ? AND UPPER(name) = ?',
				array($formid, $pid, strtoupper($form['name']))) ? true : false);

		if ($error)
			$obj->setEvent("id_model_name","onmouseover", "popup('<span class=\\\"red bold\\\">" . trans("Model already exists!") . "</span>')");
	}

	if ($error) {
		$obj->script("addClass(xajax.$('id_model_name'),'alert');");
		$obj->script("xajax.$('id_model_name').focus();");
	} else {
		if ($formid) {
			$DB->Execute('UPDATE netdevicemodels SET name = ?, alternative_name = ?, type = ? WHERE id = ?',
				array($form['name'],
					($form['alternative_name'] ? $form['alternative_name'] : NULL),
					$form['type'],
					$formid,
				));
			$obj->script("xajax_cancel_model();");
			$obj->script("self.location.href='?m=netelement&action=models&page=1&p_id=$pid';");
		} else {
			$DB->Execute('INSERT INTO netdevicemodels (netdeviceproducerid, name, alternative_name, type) VALUES (?, ?, ?, ?)',
				array($pid,
					$form['name'],
					($form['alternative_name'] ? $form['alternative_name'] : NULL),
					$form['type'],
				));

			$obj->script("xajax_cancel_model();");
			$obj->script("self.location.href='?m=netelement&action=models&page=1&p_id=$pid';");
		}
	}

	return $obj;
}

function delete_model($id) {
	global $DB;
	$obj = new xajaxResponse();

	$id = intval($id);

	$pid = $DB->GetOne('SELECT p.id FROM netdevicemodels m
		JOIN netdeviceproducers p ON (p.id = m.netdeviceproducerid) WHERE m.id = ?',
		array($id));

	$DB->Execute('DELETE FROM netdevicemodels WHERE id = ?', array($id));

	$obj->script("self.location.href='?m=netelement&action=models&page=1&p_id=$pid';");

	return $obj;
}

global $LMS,$SMARTY;
$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array(
	'cancel_producer',
	'add_producer',
	'edit_producer',
	'save_producer',
	'delete_producer',
	'cancel_model',
	'add_model',
	'edit_model',
	'save_model',
	'delete_model',
));

$SMARTY->assign('xajax', $LMS->RunXajax());

?>