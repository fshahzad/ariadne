<?php
	$ARCurrent->nolangcheck = true;
	
	if ($this->CheckLogin("layout") && $this->CheckConfig()) {
		set_time_limit(0);
		$this->resetloopcheck();

		$fstore	= $this->store->get_filestore_svn("templates");
		$svn	= $fstore->connect($this->id, $this->getdata("username"), $this->getdata("password"));
		$svn_info = $fstore->svn_info($svn);
		$repository = $svn_info['URL'];

		if (!isset($repository) || $repository == '') {
			echo $ARnls['err:svn:enterURL'];
			flush();
			return;
		} else {
			$repository = rtrim($repository, "/") . "/" . $repo_subpath;
			
			echo "\n<span class='svn_headerline'>Updating ".$this->path." from ".$repository."</span>\n";
			flush();

			// Update the templates.
			$result = $fstore->svn_update($svn);

			if ($result) {
				$updated_templates = array();
				$deleted_template = array();

				foreach ($result as $item) {
					switch ($item['status']) {
						case "A":
						case "U":
						case "M":
						case "G":
							$updated_templates[] = $item['name'];
							break;
						case "D":
							$deleted_templates[] = $item['name'];
							break;
						default:
							$updated_templates[] = $item['name'];
							break;
					}

					$props = $fstore->svn_get_ariadne_props($svn, $item['name']);
					if( $item["status"]  == "A" ) {
						echo "<span class='svn_addtemplateline'>Adding ".$this->path.$props["ar:function"]." (".$props["ar:type"].") [".$props["ar:language"]."] ".( $props["ar:default"] == '1' ? $ARnls["default"] : "")."</span>\n";
					} elseif( $item["status"] == "U" || substr(ltrim($item['name']),0,2) == 'U ' ) { // substr to work around bugs in SVN.php
						echo "<span class='svn_revisionline'>Updating ".$this->path.$props["ar:function"]." (".$props["ar:type"].") [".$props["ar:language"]."] ".( $props["ar:default"] == '1' ? $ARnls["default"] : "")."</span>\n";
					} elseif( $item["status"] == "M" || $item["status"] == "G" ) {
						echo "<span class='svn_revisionline'>Merging ".$this->path.$props["ar:function"]." (".$props["ar:type"].") [".$props["ar:language"]."] ".( $props["ar:default"] == '1' ? $ARnls["default"] : "")."</span>\n";
					} elseif( $item["status"] == "C" ) {
						echo "<span class='svn_revisionline'>Conflict ".$this->path.$props["ar:function"]." (".$props["ar:type"].") [".$props["ar:language"]."] ".( $props["ar:default"] == '1' ? $ARnls["default"] : "")."</span>\n";
					} elseif( $item["status"] == "D" ) {
						// echo "<span class='svn_deletetemplateline'>Deleted ".$this->path.$props["ar:function"]." (".$props["ar:type"].") [".$props["ar:language"]."] ".( $props["ar:default"] == '1' ? $ARnls["default"] : "")."</span>\n";
					} else {
						echo "FIXME: ";
						print_r($item);
						echo "\n";
					}
					flush();
				}

				$this->call(
					"system.svn.compile.templates.php",
					array(
						'templates'	=> $updated_templates,
						'fstore'	=> $fstore,
						'svn'		=> $svn
					)

				);

				$this->call(
					"system.svn.delete.templates.php",
					array(
						'templates'	=> $deleted_templates,
						'fstore'	=> $fstore,
						'svn'		=> $svn
					)
				);

			}

			// Run update on the existing subdirs.
			$arCallArgs['repoPath'] = $this->path;
			$arCallArgs['repository'] = $repository;
			
			$this->ls($this->path, "system.svn.update.recursive.php", $arCallArgs);

			// Create the dirs, restore them if needed.
			$dirlist = $fstore->svn_list($svn);
			if ($dirlist) {
				$arCallArgs['dirlist'] = $dirlist;
				$arCallArgs['svn'] = $svn;
				$arCallArgs['fstore'] = $fstore;
				$arCallArgs['repository'] = $repository;
				$this->call("system.svn.checkout.dirs.php", $arCallArgs);
			}
			flush();
		}
	}
?>