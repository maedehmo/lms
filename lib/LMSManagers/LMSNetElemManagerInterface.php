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
 * LMSNetElemManagerInterface
 * 
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
interface LMSNetElemManagerInterface
{
    public function GetNetElemLinkedNodes($id);

    public function NetElemLinkNode($id, $devid, $link = NULL);

    public function SetNetElemLinkType($dev1, $dev2, $link = NULL);
    
    public function IsNetElemLink($dev1, $dev2);
    
    public function NetElemLink($dev1, $dev2, $link);
    
    public function NetElemUnLink($dev1, $dev2);
    
    public function NetElemUpdate($data);
    
    public function NetElemAdd($data);

    public function NetElemAddActive($netelemdata,$netactivedata);

    public function NetElemAddPassive($netelemdata,$netpassivedata);

    public function NetElemAddCable($netelemdata,$netcabledata);

    public function NetElemAddSplitter($netelemdata,$netsplitterdata);	    
    
    public function NetElemAddMultiplexer($netelemdata,$netmultiplexerdata);

    public function NetElemAddComputer($netelemdata,$netcomputerdata);
    
    public function DeleteNetElem($id);
    
    public function NetElemDelLinks($id);
    
    public function GetNetElemActive($id);

    public function GetNetElemPassive($id);

    public function GetNetElemCable($id);

    public function GetNetElemSplitter($id);

    public function GetNetElemMultiplexer($id);

    public function GetNetElemComputer($id);

    public function GetNotConnectedElements($id);
    
    public function GetNetElemNames();
    
    public function GetNetElemList($order = 'name,asc');
    
    public function GetNetElemConnectedNames($id);
    
    public function GetNetElemLinkType($dev1, $dev2);
    
    public function CountNetElemLinks($id);
    
    public function GetNetElemIDByNode($id);

    public function GetNetElemType($id);

    public function NetElemExists($id);

    public function GetModelList($pid);

    public function GetNetElemPorts($id);

    public function GetNetElemUnconnectedList($netnodeid,$id);

    public function GetConnectionForWire($id,$srcnodeid,$dstnodeid);

    public function GetConnPossForWire($nodeid,$wireid);

    public function GetConnPossForPort($portid);

    public function GetCableDescByWire($wireid);

    public function GetCableByWire($wireid);

    public function GetElementByPort($portid);
}