<?php
	$ARCurrent->allnls = true;

	if ($this->CheckLogin("read") && $this->CheckConfig()) {

	  	require_once($this->store->get_config("code")."modules/mod_yui.php");

		$tasks = array();
		
		if( $this->CheckSilent("read") && $shortcutSidebar ) {
			$tasks[] = array(
				'href' => $this->make_ariadne_url() . "explore.html",
				'onclick' => "muze.ariadne.explore.view('".$this->path."'); return false;",
				'icon' => $AR->dir->images . 'icons/small/go.png',
				'nlslabel' => $ARnls['ariadne:browsetotarget']
			);
		}
		
		if ($this->CheckSilent("add",ARANYTYPE) && !$hideAdd) {
			$tasks[] = array(
				'href' => $this->make_ariadne_url() ."dialog.add.php",
				'onclick' => "muze.ariadne.explore.arshow('dialog.add',this.href); return false;",
				'icon' => $AR->dir->images . 'icons/small/add.png',
				'nlslabel' => $ARnls['ariadne:new']
			);
		}

		if ($this->CheckSilent("edit")) {
			$tasks[] = array(
				'href' => $this->make_ariadne_url() . "dialog.edit.php",
				'onclick' => "muze.ariadne.explore.arshow('dialog.edit',this.href); return false;",
				'icon' => $AR->dir->images . 'icons/small/edit.png',
				'nlslabel' => $ARnls['ariadne:edit']
			);
		}

		if( !$shortcutSidebar ) {
			if ($this->CheckSilent("delete")) {
				$tasks[] = array(
					'href' => $this->make_ariadne_url() . "dialog.rename.php",
					'onclick' => "muze.ariadne.explore.arshow('dialog.rename',this.href); return false;",
					'icon' => $AR->dir->images . 'icons/small/rename.png',
					'nlslabel' => $ARnls['ariadne:rename']
				);
			}
			$tasks[] = array(
				'href' => $this->make_ariadne_url() . "dialog.copy.php",
				'onclick' => "muze.ariadne.explore.arshow('dialog.copy',this.href); return false;",
				'icon' => $AR->dir->images . 'icons/small/copy.png',
				'nlslabel' => $ARnls['ariadne:copy']
			);
		
			if ($this->CheckSilent("delete")) {
				$tasks[] = array(
					'href' => $this->make_ariadne_url() . "dialog.delete.php",
					'onclick' => "muze.ariadne.explore.arshow('dialog.delete',this.href); return false;",
					'icon' => $AR->dir->images . 'icons/small/delete.png',
					'nlslabel' => $ARnls['ariadne:delete']
				);		
			}
		}
		if ($this->CheckSilent("admin") && $this->can_mogrify() ) {
			$tasks[] = array(
				'href' => $this->make_ariadne_url() . "dialog.mogrify.php",
				'onclick' => "muze.ariadne.explore.arshow('dialog.mogrify',this.href); return false;",
				'icon' => $AR->dir->images . 'icons/small/mogrify.png',
				'nlslabel' => $ARnls['ariadne:mogrify']			
			);
		}
		if ($this->CheckSilent("config")) {
			$tasks[] = array(
				'href' => $this->make_ariadne_url() . "dialog.import.php",
				'onclick' => "muze.ariadne.explore.arshow('dialog.import',this.href); return false;",
				'icon' => $AR->dir->images . 'icons/small/import.png',
				'nlslabel' => $ARnls['ariadne:import']
			);		
			$tasks[] = array(
				'href' => $this->make_ariadne_url() . "dialog.export.php",
				'onclick' => "muze.ariadne.explore.arshow('dialog.export',this.href); return false;",
				'icon' => $AR->dir->images . 'icons/small/export.png',
				'nlslabel' => $ARnls['ariadne:export']
			);
		}

		$tasks[] = array( // we use make_local_url specifically here.
			'href' => $this->make_local_url()."view.html",
			'onclick' => "muze.ariadne.explore.arshow('_new', this.href); return false;",
			'icon' => $AR->dir->images . 'icons/small/viewweb.png',
			'nlslabel' => $ARnls['ariadne:viewweb']
		);		

		$arCallArgs["sectionName"] = "tasks";
		$arCallArgs["sectionDisplayName"] = $ARnls["ariadne:options"];
		$arCallArgs["icon"] = $icon;
		
		$section = array(
			'id' => 'tasks',
			'label' => $ARnls["ariadne:options"],
			'tasks' => $tasks
		);
		if( $shortcutSidebar ) {
			if (!$ARCurrent->arTypeTree) {
				$this->call('typetree.ini');
			}
			$section['inline_icon'] = $ARCurrent->arTypeIcons[$this->type]['small'] ? $ARCurrent->arTypeIcons[$this->type]['small'] : $this->call('system.get.icon.php', array('size' => 'small'));
			$section['inline_iconalt'] = $this->type;
		}

		$section = $this->call('explore.sidebar.tasks.extra.html', array("section" => $section, "images" => $AR->dir->images));
		echo yui::getSection($section);
	}
?>