<?php
	$ARnls['install:welcome'] 		= 'Welcome to the Ariadne installation.';
	$ARnls['install:selectlanguage'] 	= 'Please select the installation language, and click "next" to continue.';
	$ARnls['install:next']			= 'Next';
	$ARnls['install:previous']		= 'Previous';
	$ARnls['install:step']			= 'Step %d';
	$ARnls['install:install_ariadne']	= 'Install Ariadne';

	$ARnls['install:language']		= 'Language';
	$ARnls['install:pre_install_checks']	= 'Pre-install checks';
	$ARnls['install:license']		= 'License';
	$ARnls['install:database']		= 'Database configuration';
	$ARnls['install:configuration']		= 'Ariadne configuration';
	$ARnls['install:install']		= 'Start installation';

	$ARnls['install:pre_install_info']	= 'Before starting the installation, several checks will be performed to ensure the requirements for Ariadne are met.';
	$ARnls['install:required_options']	= 'Required options';
	$ARnls['install:recommended_options']	= 'Recommended options';
	$ARnls['install:run_pre_install']	= 'Running pre-install checks';
	$ARnls['install:required_checks_info']	= 'If any of these checks are marked "Failed", the requirements for installing Ariadne are not met. Please take the appropriate action to fulfill the requirements before continuing the installation.';
	$ARnls['install:recommended_options_info'] = 'If any of these checks are marked "Failed", the recommended options for installing Ariadne are not met. These will not interfere with installation, but some functionality within Ariadne will not be available.';
	$ARnls['install:recheck']		= 'Re-run checks';
	$ARnls['install:check_ok']		= 'Passed';
	$ARnls['install:check_failed']		= 'Failed';
	$ARnls['install:check_php_version']	= 'PHP version 5';	
	$ARnls['install:check_database_support']= 'Supported database';
	$ARnls['install:check_webserver']	= 'Supported webserver';
	$ARnls['install:check_accept_path_info']= 'AcceptPathInfo directive in httpd.conf';
	$ARnls['install:check_zend_compat']	= 'Zend compatibility is off';
	$ARnls['install:check_ariadne_inc_read']= 'Configuration file (ariadne.inc) is readable';
	$ARnls['install:check_ariadne_path']	= 'Ariadne path in ariadne.inc points to Ariadne';
	$ARnls['install:check_ariadne_phtml_write']= 'Configuration file (ariadne.phtml) is writable';
	$ARnls['install:check_files_write']	= 'Files directory is writable';
	$ARnls['install:check_image_magick']	= 'Image Magick';
	$ARnls['install:check_exif']	= 'PHP has Exif support';
	$ARnls['install:check_svn']		= 'SVN support';	
	$ARnls['install:check_svn_write']	= 'SVN configuration directory is writable';
	$ARnls['install:check_html_tidy']	= 'HTML Tidy is installed';
	$ARnls['install:check_grep']		= 'Grep is installed';
	$ARnls['install:check_configs']		= 'Config directory is readable';
	$ARnls['install:check_ariadne_phtml']	= 'ariadne.phtml is readable';
	$ARnls['install:check_store_phtml']	= 'store.phtml is readable';
	$ARnls['install:check_axstore_phtml']	= 'axstore.phtml is readable';
	$ARnls['install:check_includes']	= 'includes directory is readable'; 
	$ARnls['install:check_loader_web_php']	= 'loader.web.php is readable';
	$ARnls['install:check_stores']		= 'stores directory is readable';
	$ARnls['install:check_selected_db_store']= 'Selected database store information is readadble';
	$ARnls['install:check_selected_db_store_install'] = 'Store installation procedure is readable';
	$ARnls['install:check_nls']		= 'NLS directory is readable';
	$ARnls['install:check_tar_class']	= 'Archive/Tar class is available';

	$ARnls['install:check_default_nls']	= 'Default nls language files are available';
	$ARnls['install:check_connect_db']	= 'Connect to database server';
	$ARnls['install:check_select_db']	= 'Database exists and can used';
	$ARnls['install:check_db_grants']	= 'Database grants are set for database user';
	$ARnls['install:check_db_is_empty']	= 'Database to install in is empty';
	$ARnls['install:check_files']		= 'Files directory exists and is readable';
	$ARnls['install:check_base_ax']		= 'Ariadne Base package exists and is readable';
	$ARnls['install:check_demo_ax']		= 'Sample sites and layouts package exists and is readable';
	$ARnls['install:check_libs_ax']		= 'Libraries package exists and is readable';
	$ARnls['install:check_docs_ax']		= 'Ariadne reference package exists and is readable';
	$ARnls['install:check_admin_password']	= 'Admin password is set and confirmed'; 
	$ARnls['install:database_configuration']= 'Database configuration';
	$ARnls['install:database_basic_settings']= 'Basic settings';
	$ARnls['install:database_info']		= 'Please supply the database information where Ariadne will be installed.';
	$ARnls['install:select_database']	= 'Select database type:';
	$ARnls['install:select_database_help'] 	= 'Select the database type from the list. This will usually be "MySQL". Only database types that are supported in PHP are listed.';
	$ARnls['install:database_host']		= 'Database hostname:';
	$ARnls['install:database_host_help']	= 'Please supply the hostname of the database server. This will usually be "localhost". In case of doubt, please check with your hosting solution provider.';
	$ARnls['install:database_user']		= 'Database username:';
	$ARnls['install:database_user_help']	= 'Enter the username that Ariadne will use to connect to the database server.';
	$ARnls['install:database_pass']		= 'Database password:';
	$ARnls['install:database_pass_help']	= 'This is the same password that you would use to connect to the database. Using a password on the database is highly recommended.';
	$ARnls['install:database_name']		= 'Database name:';
	$ARnls['install:database_name_help']	= 'This is the name of the database where Ariadne will be installed. If you are unsure what to enter here, we recommend "ariadne". Note: the database name is case sensitive.';

	$ARnls['install:ariadne_configuration']	= 'Ariadne configuration';
	$ARnls['install:ariadne_basic']		= 'Ariadne basic settings';
	$ARnls['install:ariadne_location']	= 'Path to Ariadne on disk'; 
	$ARnls['install:ariadne_location_help']	= 'This is the path where the Ariadne installation resides on the server. This should be a path outside the webroot.';
	$ARnls['install:setup_admin_acc'] 	= 'Administrator account information';
	$ARnls['install:admin_pass']		= 'Administator password';
	$ARnls['install:admin_pass_help']	= 'Enter the password for the administrator account. This password will be set on installation.';
	$ARnls['install:admin_pass_repeat']	= 'Confirm administator password';
	$ARnls['install:admin_pass_repeat_help']= 'Enter the same password as entered before. This is to ensure the passwords match.';
	$ARnls['install:install_modules']	= 'Install extra modules';
	$ARnls['install:enable_svn']		= 'Enable SVN support in Ariadne';
	$ARnls['install:install_libs']		= 'Install Ariadne basic libraries';
	$ARnls['install:install_demo']		= 'Install sample sites and layouts';
	$ARnls['install:install_docs']		= 'Install Ariadne reference site';

	$ARnls['install:problem_encountered']	= 'A problem was encountered before the installation started. No files were written, no database changes have been made.';
	$ARnls['install:please_review_problems']= 'Please review the following checks and resolve the problem.';
	$ARnls['install:installing']		= 'Starting Ariadne installation';

	$ARnls['install:running']		= 'Executing installation steps';
	$ARnls['install:write_config_file']	= 'Write configuration file';
	$ARnls['install:init_database']		= 'Initialize Ariadne database';
	$ARnls['install:install_base_package']	= 'Install Ariadne base package';
	$ARnls['install:success']		= 'Ariadne is installed! Before you move on, there are a few more things to do to make the installation complete.';

	$ARnls['install:download_config']	= 'Download configuration file';
	$ARnls['install:cant_write_config']	= 'The webserver is not able to write the configuration file.';
	$ARnls['install:to_continue']		= 'To continue installation: ';
	$ARnls['install:download']		= 'download the file and place it manually.';
	$ARnls['install:file_should_be']	= 'The file should be named "ariadne.phtml" and goes under "ariadne/lib/configs/"';
	$ARnls['install:continue_when_done']	= 'When the file has been placed, click the button below to continue.';
	$ARnls['install:continue_install']	= 'Continue installation';
	
?>
