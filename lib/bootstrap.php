<?php

	global $auth_config,$store_config,$cache_config,$session_config,$AR,$ariadne,$ax_config,$ARCurrent,$ARConfig,$ARLoader;

	if(!defined('AriadneBasePath') ) {
		define('AriadneBasePath', $ariadne);
	}

	$loaderType = ($ARLoader)?$ARLoader:'web';

	require_once(AriadneBasePath."/../vendor/autoload.php");
	require_once(AriadneBasePath.'/configs/ariadne.phtml');
	require_once(AriadneBasePath."/configs/sessions.phtml");
	require_once(AriadneBasePath."/configs/cache.phtml");
	require_once(AriadneBasePath."/configs/authentication.phtml");
	require_once(AriadneBasePath."/configs/store.phtml");
	require_once(AriadneBasePath."/ar.php");
	require_once(AriadneBasePath."/modules/mod_debug.php");
	require_once(AriadneBasePath."/modules/mod_cache.php");


	if ($workspace = getenv("ARIADNE_WORKSPACE")) {
		include_once($store_config['code']."modules/mod_workspace.php");
		$layer = workspace::getLayer($workspace);
		if (!$layer) {
			$layer = 1;
		}

		if ($wspaths = getenv("ARIADNE_WORKSPACE_PATHS")) {
			$wspaths = explode(";", $wspaths);
			foreach ($wspaths as $wspath) {
				if ($wspath != '') {
					$store_config['layer'][$wspath] = $layer;
				}
			}
		} else {
			$store_config['layer'] = array('/' => $layer );
		}
	}

	require_once(AriadneBasePath."/includes/loader.".$loaderType.".php");
	require_once(AriadneBasePath."/stores/".$store_config["dbms"]."store.phtml");

	require_once(AriadneBasePath."/modules/mod_session.phtml");
	require_once(AriadneBasePath."/modules/mod_auth/".$auth_config['method'].".php");

	include(AriadneBasePath."/nls/".$AR->nls->default);
