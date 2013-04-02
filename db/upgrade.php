<?php  //$Id: upgrade.php,v 1.6 2011-07-29 09:02:12 vf Exp $

// This file keeps track of upgrades to 
// the online_users block
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_block_use_stats_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

/// And upgrade begins here. For each one, you'll need one 
/// block of code similar to the next one. Please, delete 
/// this comment lines once this file start handling proper
/// upgrade code.

/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }

if ($result && $oldversion < 2011042400) {

    /// Define table use_stats to be created
        $table = new XMLDBTable('use_stats');

    /// Adding fields to table use_stats
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0');
        $table->addFieldInfo('contextid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0');
        $table->addFieldInfo('elapsed', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0');
        $table->addFieldInfo('events', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null, null, '0');

    /// Adding keys to table use_stats
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Adding indexes to table use_stats
        $table->addIndexInfo('index_userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->addIndexInfo('index_contextid', XMLDB_INDEX_NOTUNIQUE, array('contextid'));

    /// Launch create table for use_stats
        $result = $result && create_table($table);
    }

	if ($result && $oldversion < 2011042401) { //New version in version.php
		/*
		 * Installing use_stats service
		 */
		if (!get_record('mnet_service', 'name', 'use_stats')) {
			// Installing service
			$service = new stdclass;
			$service->name = 'use_stats';
			$service->description = get_string('use_stats_rpc_service_name', 'block_use_stats');
			$service->apiversion = 1;
			$service->offer = 1;
			if (!$serviceid = insert_record('mnet_service', $service)){
				notify('Error installing use_stats service.');
				$result = false;
			}
		}
		
		/*
		 * Installing RPC call 'get_stats'
		 */
		// Checking if it is already installed
		if (!get_record('mnet_rpc', 'function_name', 'use_stats_rpc_get_stats')) {
			
			// Creating RPC call
			$rpc = new stdclass;
			$rpc->function_name = 'use_stats_rpc_get_stats';
			$rpc->xmlrpc_path = 'blocks/use_stats/rpclib.php/use_stats_rpc_get_stats';
			$rpc->parent_type = 'block';  
			$rpc->parent = 'use_stats';
			$rpc->enabled = 0; 
			$rpc->help = 'get remotely use stats information.';
			$rpc->profile = '';
			
			// Adding RPC call
			if (!$rpcid = insert_record('mnet_rpc', $rpc)) {
				notify('Error installing use_stats RPC call "get_stats".');
				$result = false;
			} else {
				// Mapping service and call
				$rpcmap = new stdclass;
				$rpcmap->serviceid = $serviceid;
				$rpcmap->rpcid = $rpcid;
				if (!insert_record('mnet_service2rpc', $rpcmap)) {
					notify('Error mapping RPC call "get_stats" to the "use_stats" service.');
					$result = false;
				}
			}
		}
	}

    return $result;
}

?>
