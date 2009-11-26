<?php
    /******************************************************************
     pobject.phtml                                         Muze Ariadne
     ------------------------------------------------------------------
     Author: Muze (info@muze.nl)
     Date: 31 october 2002

     Copyright 2002 Muze

     This file is part of Ariadne.

     Ariadne is free software; you can redistribute it and/or modify
     it under the terms of the GNU General Public License as published 
     by the Free Software Foundation; either version 2 of the License, 
     or (at your option) any later version.
 
     Ariadne is distributed in the hope that it will be useful,
     but WITHOUT ANY WARRANTY; without even the implied warranty of
     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     GNU General Public License for more details.

     You should have received a copy of the GNU General Public License
     along with Ariadne; if not, write to the Free Software 
     Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  
     02111-1307  USA

    -------------------------------------------------------------------

     Class inheritance: 	pobject 
     Description:

       This is the class definition file of the pobject class.

    ******************************************************************/

debug("pobject: Load","object");

abstract class ariadne_object extends object { // ariadne_object class definition

	var $store;	
	var $path;
	var $data;  

	function init($store, $path, $data) {
		debug("pobject: init([store], $path, [data])","object");
		$this->store=$store;
		$this->path=$path;
		$this->data=$data;
	}

	function call($arCallFunction="view.html", $arCallArgs="") {
	/***********************************************************************
	  call tries to find the template ($arCallFunction) for the current
	  object. If it is not defined there, call will search the superclasses
	  until either it or the template 'default.phtml' is found.

	  $arCallFunction must be the name of a class or object template.
	    it can be prepended with "{classname}::". classname must be either
	    a valid ariadne class (derived from pobject). call() 
	    will then try to find the template starting at the given classname.
		e.g.:
		call("pobject::view.html") will show the view.html template of
	      pobject, with the data of the current object.

	  variables available to templates:
	  local: arCallFunction, arCallArgs, arCallTemplate, data
	  global: AR, ARConfig, ARCurrent, ARBeenHere, ARnls
	***********************************************************************/
	global $AR, $ARConfig, $ARCurrent, $ARBeenHere, $ARnls;

		debug("pobject: ".$this->path.": call($arCallFunction, ".serialize($arCallArgs).")","object","all","IN");

		// default to view.html
		if (!$arCallFunction) {
			$arCallFunction="view.html";
		}
		// clear previous results
		unset($ARCurrent->arResult);

		// callstack is needed for getvar()
		$ARCurrent->arCallStack[]=&$arCallArgs;
		// keep track of the context (php or pinp) in which the called template runs. call always sets it php, CheckConfig sets it to pinp if necessary.
		$this->pushContext(Array("arCurrentObject" => $this,"scope" => "php"));

		// convert the deprecated urlencoded arguments to an array
		if (is_string($arCallArgs)) {
			$ARCurrent->arTemp=$arCallArgs;
			$arCallArgs=Array();
			Parse_str($ARCurrent->arTemp, $arCallArgs);
		}
		// import the arguments in the current scope, but don't overwrite existing
		// variables.
		if (is_array($arCallArgs)) {
			extract($arCallArgs,EXTR_SKIP);
		}
		// now find the initial nls selection (CheckConfig is needed for per
		// tree selected defaults)
		if ($ARCurrent->nls) { 
			$this->reqnls=$ARCurrent->nls; 
		} else if ($ARConfig->cache[$this->path] && $ARConfig->cache[$this->path]->nls->default) {
			$this->reqnls = $ARConfig->cache[$this->path]->nls->default;
		} else {
			$this->reqnls=$AR->nls->default; 
		}
		if (isset($this->data->nls->list[$this->reqnls]) || !isset($this->data->nls)) {  
			// the requested language is available
			$this->nls=$this->reqnls;
			$nls=&$this->nls;
		} else {
			// the requested language is not available, use default of the
			// current object instead.
			$this->nls=$this->data->nls->default;
			$nls=&$this->nls;
			$flag=" <img class=\"flag\" src=\"".$AR->dir->images."nls/small/$nls.gif\" alt=\"\" border=\"0\"> ";
		}
		if ($nls && $this->data->$nls) {
			// now set the data and nlsdata pointers
			$this->nlsdata=$this->data->$nls;
			$nlsdata=&$this->nlsdata;
			$data=&$this->data;
		} else {
			// this object doesn't support nls data
			$this->nlsdata=$this->data;
			$nlsdata=&$this->data;
			$data=&$this->data;
		} 
		if ($this->data->custom['none']) {
			$customdata=$this->data->custom['none'];
		}
		if ($this->data->custom[$nls]) {
			$customnlsdata=$this->data->custom[$nls];
		}
		debug("pobject: call: nls set to $nls","all"); 

		$arCallFunctionOrig = $arCallFunction;
		if (strpos($arCallFunction,"::")!==false) {
			// template of a specific class defined via call("class::template");
			list($arType, $arCallFunction)=explode("::",$arCallFunction);
			$temp = explode(":", $arType );
			if( count($temp) > 1 ) {
				$libname = $temp[0];
				$arType = $temp[1];
				$arCallFunction = $libname.":".$arCallFunction;
			}
		} else {
			$arType=$this->type;
		}

		if ($arCallFunction[0] === "#") {
			$ARCurrent->arCallClassTemplate = true;
			$arCallFunction = substr($arCallFunction, 1);
		} else {
			$ARCurrent->arCallClassTemplate = false;
		}

		while ($arType!="object") {
			debug("class: $arType","object");
			// search for the template, stop at the root class ('ariadne_object')
			// (this should not happen, as pobject must have a 'default.phtml')
			$arCallTemplate=$this->store->get_config("code")."templates/".$arType."/".$arCallFunction;
			if (file_exists($arCallTemplate)) {
				// template found
				debug("pobject::call template($arCallTemplate)", "all");
				$arCallFunction = $arCallFunctionOrig;
				include($arCallTemplate);
				debug("pobject::call end template", "all");
				break;
			} else if (file_exists($this->store->get_config("code")."templates/".$arType."/default.phtml")) {
				// template not found, but we did find a 'default.phtml'
				debug("pobject::call $arType/default.phtml", "all");
				include($this->store->get_config("code")."templates/".$arType."/default.phtml");
				break;
			} else {
				if (!($arSuper=$AR->superClass[$arType])) {
					// no template found, no default.phtml found, try superclass.

					if ($subcpos = strpos($arType, '.')) {
						$arSuper = substr($arType, 0, $subcpos);
						if (!class_exists($arType)) {
							// the super class was not yet loaded, so do that now
							$this->store->newobject('', '', $arSuper, new object);
						}
					} else {
						if (!class_exists($arType)) {
							// the given class was not yet loaded, so do that now
							$this->store->newobject('','',$arType,new object);
							// include_once($this->store->get_config("code")."objects/".$arType.".phtml");

						}
						$arTemp=new $arType();
						$arSuper=get_parent_class($arTemp);
					}
					$AR->superClass[$arType]=$arSuper;
				}
				$arType=$arSuper;
				debug("try superclass: $arType","object");
			}
		}
		array_pop($ARCurrent->arCallStack);
		$this->popContext();
		debug("pobject: call: end","all","all","OUT");
		if (isset($ARCurrent->arResult)) {
			// pinp templates can return results via putvar("arResult",$result);
			$arResult=$ARCurrent->arResult;
			unset($ARCurrent->arResult);
		}
		if (isset($arResult)) {
			// only do a return if we really have something to return
			return $arResult;
		}
	}

	function ls($path="", $function="list.html", $args="") {
		debug("pobject: ls($path, $function, $args)","object");
		$path=$this->store->make_path($this->path, $path);
		return $this->store->call($function, $args, $this->store->ls($path));
	}

	function get($path, $function="view.html", $args="") {
		debug("pobject: get($path, $function, $args)","object");
		$path=$this->store->make_path($this->path, $path);
		return $this->store->call($function, $args, $this->store->get($path));
	}

	function parents($path, $function="list.html", $args="", $top="") {
		debug("pobject: parents($path, $function, $args)","object");
		if (!$top) {
			debug('no top given');
			$top=$this->currentsection();
			debug('top: '.$top);
		} else {
			debug('top: '.$top);
		}
		$path=$this->store->make_path($this->path, $path);
		return $this->store->call($function, $args, $this->store->parents($path, $top));
	}

	function find($path, $criteria, $function="list.html", $args="", $limit=100, $offset=0) {
		debug("pobject: find($path, [crit], $function, $args)","object");
		$path=$this->store->make_path($this->path, $path);
		$objects=$this->store->find($path, $criteria, $limit, $offset);
		if (!$this->store->error) {
			$result=$this->store->call($function, $args, $objects);
		} else {
			$this->error=$this->store->error;
			$result=false;
		}
		return $result;
	}

	function count_find($path='', $query='') {
		$path=$this->store->make_path($this->path, $path);
		return $this->store->count($this->store->find($path, $query, 0));
	}

	function count_ls($path) {
		return $this->store->count($this->store->ls($path));
	}

	function save($properties="", $vtype="") {
	/***********************************************************************
	  save the current object.
	  if this is a new object ($this->arIsNewObject) the path is checked and
	  the object is saved under the new path.
	***********************************************************************/
	global $AR, $ARConfig, $ARnls, $ARCurrent;
		debug("pobject: save([properties], $vtype)","object");
		debug("pobject: save: path=".$this->path,"object");
		$result=false;
		if ($this->arIsNewObject) { // save a new object
			debug("pobject: save: new object","all");
			$arNewParent=$this->make_path("..");
			$arNewFilename=substr($this->path, strlen($arNewParent), -1);
			$configcache=$ARConfig->cache[$arNewParent];
			if (!eregi("\.\.",$arNewFilename)) {
				if (eregi("^[a-z0-9_\{\}\.\:-]+$",$arNewFilename)) { // no "/" allowed, these will void the 'add' grant check.
					if (!$this->exists($this->path)) { //arNewFilename)) {
						if ($this->exists($arNewParent)) {
							if (!$config = $this->data->config) {
								$config=new object();
							}
							$wf_object = $this->store->newobject($this->path, $this->parent, $this->type, $this->data, $this->id, $this->lastchanged, $this->vtype, 0, $this->priority);
							$wf_object->arIsNewObject=true;
							if ($ARCurrent->arCallStack) {
								$arCallArgs=end($ARCurrent->arCallStack);
							}
							$arCallArgs['properties'] = $properties;

							$wf_result = $wf_object->call("user.workflow.pre.html", $arCallArgs);
							$this->error = $wf_object->error;
							$this->priority = $wf_object->priority;
							$this->data = $wf_object->data;
							$this->data->config = $config;
							$this->data->ctime=time();
							$this->data->mtime=$this->data->ctime;
							$this->data->muser=$AR->user->data->login;
							if( !$this->data->config->owner ) {
								if( !$this->data->config->owner_name) { 
									$this->data->config->owner_name=$AR->user->data->name;
								}
								$this->data->config->owner=$AR->user->data->login;
							}
							$custom = $this->getdata("custom", "none");
							@parse_str($custom);
							if (is_array($custom)) {
								reset($custom);
								foreach($custom as $nls=>$entries){
									if (is_array($entries)) {
										foreach ( $entries as $customkey => $customval ){
											$this->data->custom[$nls][$customkey] = $customval;
										}
									}
								}
							}
							// the above works because either $custom comes from the form entry, and parse_str returns an 
							// array with the name $custom, or $custom comes from the object and is an array and as such 
							// parse_str fails miserably thus keeping the array $custom intact.
							$properties["time"][0]["ctime"]=$this->data->ctime;
							$properties["time"][0]["mtime"]=$this->data->mtime;
							$properties["time"][0]["muser"]="'".AddSlashes($this->data->muser)."'";
							$properties["owner"][0]["value"]="'".AddSlashes($this->data->config->owner)."'";
							$i=0;
							if (is_array($this->data->custom)) {
								foreach($this->data->custom as $nls => $cdata) {
									foreach($cdata as $name => $value){
										// one index, this order (name, value, nls) ?
										if ($configcache->custom[$name]['property']) {
											if (is_array($value)) {
												foreach($value as $valkey => $valvalue ) {
													$properties["custom"][$i]["name"]="'".AddSlashes($name)."'";
													$properties["custom"][$i]["value"]="'".AddSlashes($valvalue)."'";
													$properties["custom"][$i]["nls"]="'".AddSlashes($nls)."'";
													$i++;
												}
											} else {
												$properties["custom"][$i]["name"]="'".AddSlashes($name)."'";
												$properties["custom"][$i]["value"]="'".AddSlashes($value)."'";
												$properties["custom"][$i]["nls"]="'".AddSlashes($nls)."'";
												$i++;
											}
										}
									}
								}
							}
							/* merge workflow properties */
							if (is_array($wf_result)) {
								foreach ($wf_result as $wf_prop_name => $wf_prop) {
									foreach ($wf_prop as $wf_prop_index => $wf_prop_record) {
										if (!isset($wf_prop_record)) {
											unset($properties[$wf_prop_name][$wf_prop_index]);
										} else {
											$record = Array();
											foreach ($wf_prop_record as $wf_prop_field => $wf_prop_value) {
												switch (gettype($wf_prop_value)) {
													case "integer":
													case "boolean":
													case "double":
														$value = $wf_prop_value;
													break;
													default:
														$value = "'".AddSlashes($wf_prop_value)."'";
														if (substr($wf_prop_value, 0, 1) === "'" && substr($wf_prop_value, -1) === "'"
															&& "'".AddSlashes(StripSlashes(substr($wf_prop_value, 1, -1)))."'" == $wf_prop_value) {
																$value = $wf_prop_value;
														}
														
												}
												$record[$wf_prop_field] = $value;
											}
											$properties[$wf_prop_name][] = $record;
										}
									}
								}
							}
							if (!$this->error) {
								if ($this->path=$this->store->save($this->path, $this->type, $this->data, $properties, $vtype, $this->priority)) {
									unset($this->arIsNewObject);
									$this->id=$this->exists($this->path);

									$config=$this->data->config; // need to set it again, to copy owner config data
									$wf_object = $this->store->newobject($this->path, $this->parent, $this->type, $this->data, $this->id, $this->lastchanged, $this->vtype, 0, $this->priority);
									$wf_object->arIsNewObject = true;
									if ($ARCurrent->arCallStack) {
										$arCallArgs=end($ARCurrent->arCallStack);
									}
									$arCallArgs['properties'] = $properties;
									$wf_result = $wf_object->call("user.workflow.post.html", $arCallArgs);
									$this->error = $wf_object->error;
									$this->priority = $wf_object->priority;
									$this->data = $wf_object->data;
									$this->data->config = $config;
									if (is_array($wf_result)) {
										foreach ($wf_result as $wf_prop_name => $wf_prop) {
											foreach ($wf_prop as $wf_prop_index => $wf_prop_record) {
												if (!isset($wf_prop_record)) {
													unset($properties[$wf_prop_name][$wf_prop_index]);
												} else {
													$record = Array();
													foreach ($wf_prop_record as $wf_prop_field => $wf_prop_value) {
														switch (gettype($wf_prop_value)) {
															case "integer":
															case "boolean":
															case "double":
																$value = $wf_prop_value;
															break;
															default:
																$value = "'".AddSlashes($wf_prop_value)."'";
																if (substr($wf_prop_value, 0, 1) === "'" && substr($wf_prop_value, -1) === "'"
																	&& "'".AddSlashes(StripSlashes(substr($wf_prop_value, 1, -1)))."'" == $wf_prop_value) {
																		$value = $wf_prop_value;
																}
														}
														$record[$wf_prop_field] = $value;
													}
													$properties[$wf_prop_name][] = $record;
												}
											}
										}
										if (!$this->store->save($this->path, $this->type, $this->data, $properties, $this->vtype, $this->priority)) {
											$this->error = $this->store->error;
										}
									}
								} else {
									$this->error=$this->store->error;
								}
								$result=$this->path;
							}
						} else {
							$this->error=sprintf($ARnls["err:noparent"],$arNewParent);
						}
					} else {
						$this->error=sprintf($ARnls["err:alreadyexists"],$arNewFilename);
					}
				} else {
					$this->error=sprintf($ARnls["err:fileillegalchars"],$arNewFilename);
				}
			} else {
				$this->error=sprintf($ARnls["err:filenamedirup"],$arNewFilename);
			}
		} else { // update an existing object
			debug("pobject: save: existing object","all");
			$configcache=$ARConfig->cache[$this->path];
			if ($this->lock()) {
				if ($this->exists($this->path)) { // prevent 'funny stuff'
					$config = $this->data->config;
					$wf_object = $this->store->newobject($this->path, $this->parent, $this->type, $this->data, $this->id, $this->lastchanged, $this->vtype, 0, $this->priority);
					if ($ARCurrent->arCallStack) {
						$arCallArgs=end($ARCurrent->arCallStack);
					}
					$arCallArgs['properties'] = $properties;
					$wf_result = $wf_object->call("user.workflow.pre.html", $arCallArgs);
					$this->error = $wf_object->error;
					$this->priority = $wf_object->priority;
					$this->data = $wf_object->data;
					$this->data->config = $config;
					$this->data->mtime=time();
					$this->data->muser=$AR->user->data->login;
					$properties["time"][0]["ctime"]=$this->data->ctime;
					$properties["time"][0]["mtime"]=$this->data->mtime;
					$properties["time"][0]["muser"]="'".AddSlashes($AR->user->data->login)."'";
					$custom = $this->getdata("custom","none");
					@parse_str($custom);
					// see comments above
					if (is_array($custom)) {
						foreach($custom as $nls => $entries){
							if (is_array($entries)) {
								foreach($entries as $customkey => $customval ){
									$this->data->custom[$nls][$customkey] = $customval;
								}
							}
						}
					}
					$i=0;
					if (is_array($this->data->custom)) {
						foreach($this->data->custom as $nls => $cdata){
							foreach($cdata as $name => $value ){
								if ($configcache->custom[$name]['property']) {
									// one index, this order (name, value, nls) ?
									if (is_array($value)) {
										foreach($value as $valkey => $valvalue){
											$properties["custom"][$i]["name"]="'".AddSlashes($name)."'";
											$properties["custom"][$i]["value"]="'".AddSlashes($valvalue)."'";
											$properties["custom"][$i]["nls"]="'".AddSlashes($nls)."'";
											$i++;
										}
									} else {
										$properties["custom"][$i]["name"]="'".AddSlashes($name)."'";
										$properties["custom"][$i]["value"]="'".AddSlashes($value)."'";
										$properties["custom"][$i]["nls"]="'".AddSlashes($nls)."'";
										$i++;
									}
								}
							}
						}
					}
					/* merge workflow properties */
					if (is_array($wf_result)) {
						foreach ($wf_result as $wf_prop_name => $wf_prop) {
							foreach ($wf_prop as $wf_prop_index => $wf_prop_record) {
								if (!isset($wf_prop_record)) {
									unset($properties[$wf_prop_name][$wf_prop_index]);
								} else {
									$record = Array();
									foreach ($wf_prop_record as $wf_prop_field => $wf_prop_value) {
										switch (gettype($wf_prop_value)) {
											case "integer":
											case "boolean":
											case "double":
												$value = $wf_prop_value;
											break;
											default:
												$value = "'".AddSlashes($wf_prop_value)."'";
												if (substr($wf_prop_value, 0, 1) === "'" && substr($wf_prop_value, -1) === "'"
													&& "'".AddSlashes(StripSlashes(substr($wf_prop_value, 1, -1)))."'" == $wf_prop_value) {
														$value = $wf_prop_value;
												}
										}
										$record[$wf_prop_field] = $value;
									}
									$properties[$wf_prop_name][] = $record;
								}
							}
						}
					}
					if (!$this->error) {
						if($this->path=$this->store->save($this->path, $this->type, $this->data, $properties, $vtype, $this->priority)){
							$result=$this->path;
							$wf_object = $this->store->newobject($this->path, $this->parent, $this->type, $this->data, $this->id, $this->lastchanged, $this->vtype, 0, $this->priority);
							if ($ARCurrent->arCallStack) {
								$arCallArgs=end($ARCurrent->arCallStack);
							}
							$arCallArgs['properties'] = $properties;
							$wf_result = $wf_object->call("user.workflow.post.html", $arCallArgs);
							$this->error = $wf_object->error;
							$this->priority = $wf_object->priority;
							$this->data = $wf_object->data;
							$this->data->config = $config;
							if (is_array($wf_result)) {
								foreach ($wf_result as $wf_prop_name => $wf_prop) {
									foreach ($wf_prop as $wf_prop_index => $wf_prop_record) {
										if (!isset($wf_prop_record)) {
											unset($properties[$wf_prop_name][$wf_prop_index]);
										} else {
											$record = Array();
											foreach ($wf_prop_record as $wf_prop_field => $wf_prop_value) {
												switch (gettype($wf_prop_value)) {
													case "integer":
													case "boolean":
													case "double":
														$value = $wf_prop_value;
													break;
													default:
														$value = "'".AddSlashes($wf_prop_value)."'";
														if (substr($wf_prop_value, 0, 1) === "'" && substr($wf_prop_value, -1) === "'"
															&& "'".AddSlashes(StripSlashes(substr($wf_prop_value, 1, -1)))."'" == $wf_prop_value) {
																$value = $wf_prop_value;
														}
												}
												$record[$wf_prop_field] = $value;
											}
											$properties[$wf_prop_name][] = $record;
										}
									}
								}
								if (!$this->store->save($this->path, $this->type, $this->data, $properties, $this->vtype, $this->priority)) {
									$this->error = $this->store->error;
								}
							}
							$this->data->config = $config;
							//$this->ClearCache($this->path, true, false);
						} else {
							$this->error = $this->store->error;
							$result = false;
						}
					}
				} else {
					$this->error=$ARnls["err:corruptpathnosave"];
				}
				$this->unlock();
			} else {
				$this->error=$ARnls["err:objectalreadylocked"];
			}
		}
		if ($this->data->nls->list[$this->nls]) {
			$mynlsdata=$this->data->{$this->nls};
		} else if ($this->data->nls->default) {
			$mynlsdata=$this->data->{$this->data->nls->default};
		} else {
			$mynlsdata=$this->data;
		}
		unset($this->nlsdata);
		$this->nlsdata=$mynlsdata;
		debug("pobject: save: end","all");
		return $result;
	}

	function link($to) {
		debug("pobject: link({$this->path} => $to)","object");
		return $this->store->link($this->path, $this->make_path($to));
	}

	function delete() {
	global $ARCurrent;
		debug("pobject: delete({$this->path})","object");
		$result	= false;
		if ($ARCurrent->arCallStack) {
			$arCallArgs=end($ARCurrent->arCallStack);
		}
		$this->call("user.workflow.delete.pre.html", $arCallArgs);
		if (!$this->error) {
			if ($this->store->delete($this->path)) {
				$this->call("user.workflow.delete.post.html", $arCallArgs);
				if (!$this->error) {
					$result = true;
				}
			}
		}
		return $result;
	}

	function purge() {
		debug("pobject: purge({$this->path})","object");
		return $this->store->purge($this->path);
	}

	function exists($path) {
		debug("pobject: exists($path)","object");
		$path=$this->make_path($path);
		return $this->store->exists($path);
	}

	function make_path($path="") {
		debug("pobject: make_path($path)","object");
		return $this->store->make_path($this->path, $path);
	}

	function make_url($path="", $nls=false, $session=true, $https=NULL, $keephost=false) {
		global $ARConfig, $AR, $ARCurrent;
		debug("pobject: make_url($path) (from: ".$this->path.")","object");

		$rootoptions=$this->store->rootoptions;
		if (!$session || ($nls !== false)) {
			$rootoptions = "";
			if ($session && $ARCurrent->session->id && !$AR->hideSessionIDfromURL) {
				$rootoptions .= "/-".$ARCurrent->session->id."-";
			}
			if ($nls) {
				$rootoptions_nonls = $rootoptions;
				$rootoptions .= '/'.$nls;
			}
		}
		$object_path=$this->path;
		$path=$this->make_path($path);

		// now run CheckConfig and get the parentsite of the path found
		if (!$temp_config=$ARConfig->cache[$path]) {
			$temp_path = $path;
			while (!($temp_site = $this->currentsite($temp_path)) && $temp_path!='/') {
				$temp_path = $this->make_path($temp_path.'../');
			}
			$temp_config=$ARConfig->cache[$temp_site];
		}

		if (!$nls && ($AR->host == $temp_config->root["value"]) ||
			($nls && $AR->host == $temp_config->root['list']['nls'][$nls])) {
			$keephost = false;
		}
		if (!$keephost) {
			if ($nls) {
				$url=$temp_config->root["list"]["nls"][$nls];
				if ($url) {
					if (substr($url, -1)=='/') {
						$url=substr($url, 0, -1);
					}
					$url .= $rootoptions_nonls;
				}
			}
			if (!$url) {
				$url=$temp_config->root["value"].$rootoptions;
			}
			$url.=substr($path, strlen($temp_config->root["path"])-1);
			debug("make_url: ".$temp_config->root["path"]." = $temp_site : $temp_url","all");

			if (is_bool($https)) {
				if ($https) {
					if ($AR->https) {
						$url = ereg_replace('^http:', 'https:', $url);
					}
				} else {
					$url = ereg_replace('^https:', 'http:', $url);
				}
			}
		} else {
			if ($AR->host == $temp_config->root["value"]) {
				$url=$temp_config->root["value"].$rootoptions;
				$url.=substr($path, strlen($temp_config->root["path"])-1);
			} else {
				$url=$AR->host.$AR->root.$rootoptions.$path;
			}
		}
		return $url;
	}

	function make_local_url($path="", $nls=false, $session=true, $https=NULL) {
		global $ARCurrent, $ARConfig, $AR;
		$site = false;
		$path = $this->make_path($path);
		$checkpath = $path;

		$redirects = $ARCurrent->shortcut_redirect;
		if (is_array($redirects)) {
			$newpath = $checkpath;
			$c_redirects = count($redirects);
			$c_redirects_done = 0;
			while (count($redirects) && ($redir = array_pop($redirects)) && $redir['keepurl'] && substr($newpath, 0, strlen($redir['dest'])) == $redir['dest']) {
				$c_redirects_done++;
				$newpath = $redir['src'].substr($newpath, strlen($redir['dest']));
			}
			if ($c_redirects_done == $c_redirects) {
				$checkpath = $redir['src'];
			}
		}

		do {
			if (!$config=$ARConfig->cache[$checkpath]) {
				$config=$this->loadConfig($checkpath);
			}
			if ($config) {
				if ($config->root['value']==$AR->host) {
					$site=$config->site;
				}
			}
			$prevpath=$checkpath;
			$checkpath=$this->make_path($checkpath."../");
		} while ($prevpath!=$checkpath && !$site);
		if (!$site) {
			$site='/';
		}
		$site_url=$this->make_url($site, $nls, $session, $https, true);
		if (substr($site_url, 0, strlen($AR->host))!=$AR->host) {
			$site_url=$this->make_url($site, $nls, $session, $https, true);
		}
		if ($newpath) { // $newpath is the destination of a shortcut redirection, with keepurl on
			$rest=substr($newpath, strlen($site));
		} else {
			$rest=substr($path, strlen($site));
		}
		return $site_url.$rest;
	}

	function AR_implements($implements) {
		debug("pobject: implements($implements)","object");
		$type = current(explode(".",$this->type)); 
		return $this->store->AR_implements($type, $implements); 
	}

	function getlocks() {
		global $AR;
		debug("pobject: getlocks()","object");
		if ($this->store->mod_lock) {
			$result=$this->store->mod_lock->getlocks($AR->user->data->login);
		} else {
			$result="";
		}
		return $result;
	}

	function lock($mode="O", $time=0) {
	global $AR;
		debug("pobject: lock($mode, $time)","object");
		if ($this->store->mod_lock) {
			$result=$this->store->mod_lock->lock($AR->user->data->login,$this->path,$mode,$time);
		} else {
			$result=true; // no lock module, so lock is 'set'
		}
		return $result;
	}

	function unlock() {
	global $AR;
		debug("pobject: unlock()","object");
		if ($this->store->mod_lock) {
			$result=$this->store->mod_lock->unlock($AR->user->data->login,$this->path);
		} else {
			$result=true;
		}
		return $result;
	}

	function touch($id=0, $timestamp=-1) {
		if (!$id) {
			$id = $this->id;
		}
		$result = $this->store->touch($id, $timestamp);
		if ($this->store->error) {
			$this->error = $this->store->error;
		}
		return $result;
	}

	function load_properties() {
		debug("pobject: load_properties()","object");
		return $this->store->load_properties($this->id);
	}

	function _load_properties() {
		debug("pobject: load_properties()","object");
		return $this->store->load_properties($this->id);
	}

	function load_property($property) {
		debug("pobject: load_property($property)","object");
		return $this->store->load_property($this->id,$property);
	}

	function _load_property($property) {
		debug("pobject: _load_property($property)","object");
		return $this->store->load_property($this->id,$property);
	}

	function GetValidGrants($path="") {
	/********************************************************************

	  This function finds all grants in effect on this object for the
	  logged in user! $AR->user must already be set.
	
	  Grants are checked in the following way:
	  1) First all parents of this object are checked for grants for this
	     specific user. The 'nearest' grants are valid, and the path of
	     parent that set these grants will be the upper limit for the 
	     checking of group grants.
	  2) Now all groups of which the user is a member are checked for
	     grants. Likewise, all parents are checked for group grants, upto 
	     but not including the upperlimit as set in 1. All group grants 
	     found are merged into one grants list. 
	  3) If there are gropup grants, this means that there are group 
	     grants set in a parent nearer to this object than the user grants
	     and therefore the groupgrants must be merged with the
	     usergrants.
	
	  this results in:
	  1	/		user: read edit		group: none
	  2	/dir/					group: read
	  3	/dir2/		user: none		group: read
	  4	/dir/dir3/				group2: edit
	  case 1: the user takes precedence over the group, grants are 'read edit'
	  case 2: groupgrants are merged with usergrants, as its grants are set 
	          in a 'nearer' parent (itself). grants are 'read edit'.
	  case 3: user takes precedence again. grants are 'none'.
	  case 4: All group grants are merged with the usergrants.
	          Therefore the grants are 'none read edit'.
	********************************************************************/

	global $AR;

		debug("pobject: GetValidGrants()","object");
		if ($AR->user) { 	// login and retrieval of user object 
			if (!$path) {
				$path=$this->path;
			}
			if (!$AR->user->grants[$path]) {
				$grants=Array();
				$userpath=$AR->user->FindGrants($path, $grants);
				// if not already done, find all groups of which the user is a member
				if (!is_array($AR->user->externalgroupmemberships) || sizeof($AR->user->externalgroupmemberships)==0) {
					$criteria["members"]["login"]["="]="'".AddSlashes($AR->user->data->login)."'";
				} else {
					// Use the group memberships of external databases (e.g. LDAP)
					$criteria="members.login='".AddSlashes($AR->user->data->login)."'";
					foreach (array_keys($AR->user->externalgroupmemberships) as $group) {
						$criteria.=" or login.value='".AddSlashes($group)."'";
					}
				}
				if (!$AR->user->groups) {
					$groups=$this->find("/system/groups/",$criteria, "system.get.phtml");
					if (is_array($groups)) {
						foreach($groups as $group ){
							if (is_object($group)) {
								$AR->user->groups[$group->data->login] = $group;
							}
						}
					}
					if (!$AR->user->groups["public"]) {
						if ($public=current($this->get("/system/groups/public/", "system.get.phtml"))) {
							$AR->user->groups["public"] = $public;
						}
					}
				}
				if ($AR->user->groups) {
					/* check for owner grants (set by system.get.config.phtml) */
					if (is_array($AR->user->ownergrants)) {
						if (!$AR->user->groups["owner"]) {
							$AR->user->groups["owner"] = @current($this->get("/system/groups/owner/", "system.get.phtml"));
						}
						$AR->user->groups["owner"]->data->config->usergrants = $AR->user->ownergrants;
					}
					foreach($AR->user->groups as $group){
						$groupgrants=Array();
						if (is_object($group)) {
							$group->FindGrants($path, $groupgrants, $userpath);
							if (is_array($grants)) {
								foreach($groupgrants as $gkey => $gval ){
									if (is_array($grants[$gkey]) && is_array($gval)) {
										$grants[$gkey]=array_merge($gval, $grants[$gkey]);
									} else 
									if ($gval && !is_array($gval)) {
										$grants[$gkey] = $gval;
									} else
									if ($gval && !$grants[$gkey]) {
										$grants[$gkey] = $gval;
									}
								}
							} else {
								$grants = $groupgrants;
							}
						}
					}
				}
				
				if( is_array($AR->suGrants[$path]) ) {
					$sugrants = $AR->suGrants[$path];
					if (is_array($grants)) {
						foreach($sugrants as $gkey => $gval ){
							if (is_array($grants[$gkey]) && is_array($gval)) {
								$grants[$gkey]=array_merge($gval, $grants[$gkey]);
							} else 
							if ($gval && !is_array($gval)) {
								$grants[$gkey] = $gval;
							} else
							if ($gval && !$grants[$gkey]) {
								$grants[$gkey] = $gval;
							}
						}
					} else {
						$grants = $sugrants;
					}
				}			
				$AR->user->grants[$path]=$grants;
			}
			$grants=$AR->user->grants[$path];	

		}
		debug("pobject: GetValidGrants(user:".$AR->user->data->login."): end ( ".serialize($grants)." )","all");
		return $grants;
	}


	function pushContext($context) {
	global $AR;
		if (!$AR->context) {
			$AR->context = Array();
		} else {
			$context = array_merge(end($AR->context), $context);
		}
		array_push($AR->context, $context);
	}

	function setContext($context, $level=0) {
	global $AR;
		if (is_array($AR->context)) {
			$AR->context[sizeof($AR->context)-(1+$level)]=$context;
		}
	}

	function popContext() {
	global $AR;
		if (is_array($AR->context)) {
			$result = array_pop($AR->context);
		}
		return $result;
	}

	function getContext($level=0) {
	global $AR;
		if (is_array($AR->context)) {
			$result = $AR->context[sizeof($AR->context)-(1+$level)];
		}
		return $result;
	}

	function CheckLogin($grant, $modifier=ARTHISTYPE) {
	global $AR,$ARnls,$ARConfig,$ARCurrent,$ARPassword,$ARCookie,$session_config,$ARConfigChecked;
		debug("pobject: CheckLogin($grant) on ".$this->path,"object");
		if (!$this->store->is_supported("grants")) {
			debug("pobject: store doesn't support grants");
			return true;
		}
		if ($modifier==ARTHISTYPE) {
			$modifier=$this->type;
		}

		/* load config cache */
		if (!$ARConfig->cache[$this->path]) {
			// since this is usually run before CheckConfig, make sure
			// it doesn't set cache time
			$realConfigChecked = $ARConfigChecked;
			$ARConfigChecked = true;
			$this->loadConfig();
			$ARConfigChecked = $realConfigChecked;
		}

		if ($AR->user->data->login!="admin" && !$AR->user->grants[$this->path]) {
			$AR->user->grants[$this->path]=$this->GetValidGrants();
			debug("usergrants: ".$AR->user->grants[$this->path]);
		}
		if ($AR->user->data->login!="public") {
			// Don't remove this or MSIE users won't get uptodate pages...
			ldSetClientCache(false);
		}

		debug("checking grants for ".$this->path);
		$grants=$AR->user->grants[$this->path];
		if ( 	( !$grants[$grant] 
					|| ( $modifier && is_array($grants[$grant]) && !$grants[$grant][$modifier] )
				) && $AR->user->data->login!="admin" ) {
			// do login
			$continue=false;
			debug("pobject: CheckLogin: insufficient grants (".$AR->user->data->login.")","object");
			$arLoginMessage = $ARnls["accessdenied"];
			ldAccessDenied($this->path, $arLoginMessage);
			$result=false;
			debug("pobject: CheckLogin: end (failed)","all");
		} else {
			debug("pobject: CheckLogin: end","all");
			$result=($grants || ($AR->user->data->login=="admin"));
		}

		$ARCurrent->arLoginSilent=1;
		return $result;
	}


	function CheckPublic($grant, $modifier=ARTHISTYPE) {
	global $AR;

		$result=false;
		if (!$AR->public) {
			$this->pushContext(Array("scope" => "php"));
				$AR->public=current($this->get("/system/users/public/", "system.get.phtml"));
			$this->popContext();
		}
		if ($AR->public) {
			$AR->private=$AR->user;
			$AR->user=$AR->public;
			$result=$this->CheckSilent($grant, $modifier);
			$AR->user=$AR->private;
		}
		return $result;
	}

	function CheckSilent($grant, $modifier=ARTHISTYPE, $path=".") {
	global $AR, $ARConfig;
		$path = $this->make_path($path);
		debug("pobject: CheckSilent($grant, $modifier, $path)","object");
		if ($modifier==ARTHISTYPE) {
			$modifier=$this->type;
		}
		$result=false;

		/* load config cache */
		if (!$ARConfig->cache[$path]) {
			$this->loadConfig($path);
		}
		if ($AR->user->data->login=="admin") {
			$result=1;
		} else if ($grants=$AR->user->grants[$path]) {
			$result=$grants[$grant];
		} else {
			$grants=$this->GetValidGrants();
			$result=$grants[$grant];
		}
		if ($modifier && is_array($result)) {
			$result=$result[$modifier];
		}
		debug("pobject: CheckSilent: result: ".serialize($grants));
		debug("pobject: CheckSilent: end","all");
		return $result;
	}

	function CheckNewFile($newfilename) {
	/**********************************************************************

	  This function performs all the necessary checks on a path to see
	whether it's a valid path for a new object. This consists of:
	1) checking for invalid characters, valid chars are "a-zA-Z0-9./_-"
	2) checking whether the path starts and ends with a "/".
	3) checking whether the path doesn't exist already.
	4) checking whether the parent exists.
	
	if all this checks out, it returns 1. If not, $this->error is set to
	the correct error message.
	
	**********************************************************************/

		debug("pobject: CheckNewFilename($newfilename)","object");
		$this->error="";
		if (eregi("^/[a-z0-9\./_-]*/$",$newfilename)) {
			if (!$this->store->exists($newfilename)) {
				$parent=$this->store->make_path($newfilename, "..");
				if ($this->store->exists($parent)) {
					$result=1;
				} else {
					$this->error=sprintf($ARnls["err:filenameinvalidnoparent"],$newfilename,$parent);
				}
			} else {
				$this->error=sprintf($ARnls["err:chooseotherfilename"],$newfilename);
			}
		} else {
			$this->error=sprintf($ARnls["err:fileillegalchars"],$newfilename)." ".$ARnls["err:startendslash"];
		}
		debug("pobject: CheckNewFilename: end","all");
		return $result;
	}

	function resetConfig($path='') {
	global $ARConfig;
		$path = $this->make_path($path);
		debug("resetConfig: $path");
		if ($ARConfig->cache[$path]) {
			foreach ($ARConfig->cache as $cachepath => $cache) {
				if (strpos($cachepath, $path) === 0) {
					debug("resetConfig: removing configcache from $cachepath");
					unset($ARConfig->cache[$cachepath]);
				}
			}
		}
	}

	function loadConfig($path='') {
	global $ARConfig, $ARConfigChecked;
		$configChecked = $ARConfigChecked;
		$result=false;
		$path=$this->make_path($path);
		$parent=$this->make_path($path.'../');
		if (!$ARConfig->cache[$path]) {
			if (($this->path == $path && !$this->arIsNewObject) || $this->exists($path)) {
				$this->pushContext(Array("scope" => "php"));
				$this->get($path, "system.get.config.phtml");
				$this->popContext();
				$config=$ARConfig->cache[$path];
			} else if ($path === '/') {
				// special case: / doesn't exists in the store
				$config=$ARConfig->cache['..'];
			} else {
				if (!$ARConfig->cache[$parent]) {
					$this->pushContext(Array("scope" => "php"));
					$this->get($parent, 'system.get.config.phtml');
					$this->popContext();
				}
				$config=$ARConfig->cache[$parent];
			}
		} else {
			$config=$ARConfig->cache[$path];
		}

		// restore old ARConfigChecked state
		$ARConfigChecked = $configChecked;
		if (is_object($config)) {
			$result = clone($config);
		}
		return $result;
	}


	// TODO: look for a way to merge loadConfig and loadUserConfig into one function

	function loadUserConfig($path='') {
	global $ARConfig;
		$path = $this->make_path($path);
		$parent = $this->make_path($path.'../');

		$this->loadConfig($path);
		if (!$ARConfig->pinpcache[$path]) {
			$config = $ARConfig->pinpcache[$parent];
		} else {
			$config = $ARConfig->pinpcache[$path];
		}
		return (array)$config;
	}

	function loadLibrary($name, $path) {
	global $ARConfig;
		$path=$this->make_path($path);
		if ($name===ARUNNAMED) {
			if (strstr($path, $this->path)===0) {
				error('You cannot load an unnamed library from a child object.');
			} else {
				if (!$ARConfig->libraries[$this->path]) {
					$ARConfig->libraries[$this->path]=array();
				}
				array_unshift($ARConfig->libraries[$this->path],$path);
			}
		} else if ($name && is_string($name)) {
			$this->loadConfig($this->path);
			$ARConfig->libraries[$this->path][$name]=$path;
			$ARConfig->cache[$this->path]->libraries[$name]=$path;
			$ARConfig->pinpcache[$this->path]["library"][$name] = $path;
		} else if (is_int($name)) {
			$this->loadConfig($this->path);
			$ARConfig->libraries[$this->path][$name]=$path;
			// make sure that unnamed libraries don't get added to the configcache
			unset($ARConfig->cache[$this->path]->libraries[$name]);
			unset($ARConfig->pinpcache[$this->path]["library"][$name]);
		} else {
			error('Illegal library name: '.$name);
		}
	}


	function getPinpTemplate($arCallFunction='view.html', $path=".", $top="", $inLibrary = false, $librariesSeen = null, $arSuperContext="") {
	global $ARCurrent, $ARConfig, $AR;
		debug("getPinpTemplate: function: $arCallFunction; path: $path; top: $top; inLib: $inLibrary; startType: $arStartType");
		$result = Array();
		if (!$top) {
			$top = '/';
		}
		if (($libpos=strpos($arCallFunction,":"))!==false && $libpos!==strpos($arCallFunction, "::")) {
			// template of a specific library defined via call("library:template");
			$arLibrary = substr($arCallFunction, 0, $libpos);
			if ($arLibrary == 'current') {
				// load the current arLibrary 
				$context = $this->getContext(1);
				$arLibrary = $context['arLibrary'];
				$arLibraryPath = $context['arLibraryPath'];
			} else {
				$config = $this->loadConfig($path);
				$arLibraryPath = $config->libraries[$arLibrary];
			}
			$arCallFunction = substr($arCallFunction, $libpos+1);
			debug("getPinpTemplate: searching for lib: '$arLibrary'; '$arCallFunction' from '$path'");
			if ($arLibraryPath) {
				//echo $config->libraries[$library];
				debug("getPinpTemplate: found library '$arLibrary'. Searching for $arCallFunction on '".$config->libraries[$arLibrary]."' up to '$top'");
				$librariesSeen[$arLibraryPath] = true;
				$result = $this->getPinpTemplate($arCallFunction, $arLibraryPath, $top, true, &$librariesSeen, &$arSuperContext);
				$result["arLibrary"] = $arLibrary;
				$result["arLibraryPath"] = $arLibraryPath;
			} else {
				debug("getPinpTemplate: library '$arLibrary' not in config");
			}
			return $result;
		}
		if (strpos($arCallFunction,"::")!==false) {
			// template of a specific class defined via call("class::template");
			list($arCallType, $arCallFunction)=explode("::",$arCallFunction);
		} else {
			$arCallType=$this->type;
		}
		$path = $this->make_path($path);
		debug("getPinpTemplate: searching for template ($arCallType::$arCallFunction) on $path, start with $arStartType; ");



		/* first check current templates */
		if ($this->path == $path) {
			$curr_templates = $this->data->config->pinp;
		} else {
			$config = $this->loadConfig($path);
			debug("getPinpTemplate: config for '$path' loaded' (inLibrary: $inLibrary)");
			$curr_templates = $config->templates;
		}

		$checkpath=$path;
		$lastcheckedpath="";
		$arCallClassTemplate = $ARCurrent->arCallClassTemplate;
		$arSetType = $arCallType;
		while (!$arCallClassTemplate && !$arCallTemplate && $checkpath!=$lastcheckedpath) {
			debug("getPinpTemplate: loop (".$arCallClassTemplate.")(".$arCallTemplate.")(".$checkpath.")!=(".$lastcheckedpath.")","all");
			$lastcheckedpath = $checkpath;
			$arType = $arSetType;
			while ($arType!='ariadne_object' && !$arCallTemplate) {
				if (!$arSuperContext && ($arTemplateId=$curr_templates[$arType][$arCallFunction][$this->reqnls])) {
					$arCallTemplate=$arType.".".$arCallFunction.".".$this->reqnls;
					$arTemplateNls=$this->reqnls;
				} else if (!$arSuperContext && ($arTemplateId=$curr_templates[$arType][$arCallFunction]['any'])) {
					$arCallTemplate=$arType.".".$arCallFunction.".any";
					$arTemplateNls="any";
				} else {
					if ($arSuperContext['path'] == $checkpath 
							&& $arSuperContext['function'] == $arCallFunction
								&& $arSuperContext['type'] == $arType
					) {
							debug("getPinpTemplate: arSuperContext template found, toggling: path: $checkpath; function: $arCallFunction; type: $arType");
							$arSuperContext = "";
					}

					if (!($arSuper=$AR->superClass[$arType])) {
						// no template found, no default.phtml found, try superclass.
						if ($subcpos = strpos($arType, '.')) {
							$arSuper = substr($arType, 0, $subcpos);
							$subclass = substr($arType, $subcpos+1);
							if (!class_exists($arSuper)) {
								$this->store->newobject('', '', $arSuper, new object);
							}
						} else {
							if (!class_exists($arType)) {
								// the given class was not yet loaded, so do that now
								$arTemp=$this->store->newobject('','',$arType,new object);
							} else {
								$arTemp=new $arType();
							}
							$arSuper=get_parent_class($arTemp);
						}
						$AR->superClass[$arType]=$arSuper;
					}
					$arType=$arSuper;
				}
			}
			if ($inLibrary) {
				debug("gePinpTemplate: checking for library endpoint;  path: $checkpath; type: ".$ARConfig->cache[$checkpath]->type.";");
				if ($ARConfig->cache[$checkpath]->type == 'psection') {
					debug("getPinpTemplate: quiting library search as we have encountered our end point; $checkpath");
					// break search operation when we have searched a 
					// psection object
					break;
				}
			}
			if ($ARConfig->libraries[$checkpath] && !$arTemplateId) {
				foreach ($ARConfig->libraries[$checkpath] as $library => $path) {
					if (is_int($library) && !$librariesSeen[$path]) {
						$librariesSeen[$path] = true;
						if ($arCallFunction != 'config.ini') {
							debug("getPinpTemplate: going to search in UNNAMED library '$library' => '$path' for ($arCallType::)$arCallFunction");
							$template = $this->getPinpTemplate("$arCallType::$arCallFunction", $path, '', true, &$librariesSeen, &$arSuperContext);
							if ($template["arTemplateId"]) {
								extract($template);
								$arLibrary = $library;
								$arLibraryPath = $path;
								$arType = $arCallTemplateType;
								$lastcheckedpath = $checkpath = $arCallTemplatePath;
								debug("getPinpTemplate: found $arCallFunction in library $arLibrary (arCallTemplatePath: ".serialize($arCallTemplatePath).")", "all");
								break; // jump out of the foreach loop
							}
						}
					} else {
						debug("getPinpTemplate: skipping $path - recurse fault", "all");
					}
				}
			}

			debug("getPinpTemplate: checkpath = (".$checkpath.")","all");
			if ($checkpath == $top) {
				break;
			}
			$checkpath=$this->store->make_path($checkpath, "..");
			debug("getPinpTemplate: after make_path  (".$checkpath.")","all");
			$config = $this->loadConfig($checkpath);
			$curr_templates = $config->templates;
			$arSetType = $arCallType;
		}
		debug("getPinpTemplate: while loop ended","all");

		$result["arTemplateId"] = $arTemplateId;
		$result["arCallTemplate"] = $arCallTemplate;
		$result["arCallType"] = $arCallType;
		$result["arCallTemplateType"] = $arType;
		$result["arCallTemplatePath"] = $lastcheckedpath;
		$result["arLibrary"] = $arLibrary;
		$result["arLibraryPath"] = $arLibraryPath;
		$result["arLibrariesSeen"] = $librariesSeen;

//		echo "getPinpTemplateResult:\n";
//		print_r($result);
		return $result;
	}

	function CheckConfig($arCallFunction="", $arCallArgs="") {
	// returns true when cache isn't up to date and no other template is
	// defined for $path/$function. Else it takes care of output to the
	// browser.
	// All these templates must exist under a fixed directory, $AR->dir->templates
	global $nocache, $AR, $ARConfig, $ARCurrent, $ARBeenHere, $ARnls, $ARConfigChecked;
		debug("pobject::CheckConfig($arCallFunction, $arCallArgs)(".$this->path.")","object");
		$MAX_LOOP_COUNT=10;


		// system templates (.phtml) have $arCallFunction=='', so the first check in the next line is to
		// make sure that loopcounts don't apply to those templates.
		if ($arCallFunction && $ARBeenHere[$this->path][$arCallFunction]>$MAX_LOOP_COUNT) { // protect against infinite loops
			error(sprintf($ARnls["err:maxloopexceed"],$this->path,$arCallFunction,$arCallArgs));
			$this->store->close();
			exit();
		} else {
			$ARBeenHere[$this->path][$arCallFunction]+=1;

			// this will prevent the parents from setting the cache time
			$initialConfigChecked = $ARConfigChecked;
			$ARConfigChecked = true;
			$config = $this->loadConfig();
			$ARConfigChecked = $initialConfigChecked;

			$ARConfig->nls=$config->nls;
			// if a default language is entered in a parent and no language is
			// explicitly selected in the url, use that default. 
			// The root starts with the system default (ariadne.phtml config file)
			if (!$ARCurrent->nls && $config->root['nls']) {
				$this->reqnls = $config->root['nls'];
				if (!$initialConfigChecked) {
					$ARCurrent->nls = $this->reqnls;
				}
			} else if ($config->nls->default && !$ARCurrent->nls) {
				$this->reqnls=$config->nls->default;
				$this->nls=$this->reqnls;
				if (!$initialConfigChecked) {
					$ARCurrent->nls = $this->nls;
				}
			}
			$nls=&$this->nls;
			$reqnls=&$this->reqnls;
			if (!$ARCurrent->arContentTypeSent) {
				ldHeader("Content-Type: text/html; charset=UTF-8");
				$ARCurrent->arContentTypeSent = true;
			}

/*			// FIXME: the acceptlang code works a bit too well.. it overrides psite configuration settings.

			if ($ARCurrent->acceptlang && !$ARCurrent->nls) {
				if ($ARCurrent->acceptlang && is_array($this->data->nls->list)) {
					$validlangs = array_intersect(array_keys($ARCurrent->acceptlang), array_keys($this->data->nls->list));
				}
				if ($validlangs) {
					$reqnls=array_shift($validlangs);
					$ARCurrent->nls = $reqnls;
				}
			}
*/
			// if this object isn't available in the requested language, show
			// a language select dialog with all available languages for this object.
			if (isset($this->data->nls) && !$this->data->name) {
				if (!$ARCurrent->forcenls && (!isset($this->data->nls->list[$reqnls]) || !$config->nls->list[$reqnls])) {
					if (!$ARCurrent->nolangcheck ) {
						$ARCurrent->nolangcheck=1;
						$arCallArgs["arOriginalFunction"] = $arCallFunction;
						$this->call("user.languageselect.html", $arCallArgs);
						return false;
					} else {
						$this->nlsdata=$this->data->$nls;
					}
				} else {
					$this->nlsdata=$this->data->$reqnls;
				}
			} 
			$ARCurrent->nolangcheck=1;
			if ($this->data->custom['none']) {
				$this->customdata=$this->data->custom['none'];
			}
			if ($this->data->custom[$nls]) {
				$this->customnlsdata=$this->data->custom[$nls];
			}
			if (	(	$config->cache && ($config->cache!=-1) ) &&
					(	$arCallFunction	&& !$ARConfigChecked		) && 
					( 	!$nocache 									) && 
					(	$AR->OS=="UNIX" || 
						( 	(count($_POST)==0) && 
							(count($_GET)==0) 			) 	) &&
					( 	$AR->user->data->login=="public" )
			   ) {
				// caching is on and enabled in loader and user is public and template is not 'protected'.
				$ARCurrent->cachetime=$config->cache;
				// start output buffering...	
				debug("CheckConfig: ob_start()","all");
				// if output compression is on, we don't want
				// to start another output buffer
				if (!$AR->output_compression) {
					ob_start();
				}
			} else {
				debug("CheckConfig: caching disabled","all");
				debug("             config->cache: ".$config->cache."; nocache: ".$nocache."; user: ".$AR->user->data->login."; os: ".$AR->OS,"all");
			}

			/*
				Set ARConfigChecked to true to indicate that we have been here
				earlier.
			*/
			$ARConfigChecked = true;
			if ($arCallFunction) { // don't search for templates named ''
				// FIXME: Redirect code has to move to getPinpTemplate()
				$redirects	= $ARCurrent->shortcut_redirect;
				if (is_array($redirects)) {
					$redirpath = $this->path;
					while (!$template['arTemplateId'] && 
								($redir = array_pop($redirects)) &&
									$redir["keepurl"] && 
										(substr($redirpath, 0, strlen($redir["dest"])) == $redir["dest"])
					) {
						$template = $this->getPinpTemplate($arCallFunction, $redirpath, $redir["dest"]);
						$redirpath = $redir['src'];
					}

					if (!$template["arTemplateId"] && $redirpath) {
						$template = $this->getPinpTemplate($arCallFunction, $redirpath);
					}
				}
				if (!$template["arTemplateId"]) {
					$template = $this->getPinpTemplate($arCallFunction);
				}

				if ($template["arCallTemplate"] && $template["arTemplateId"]) {
					debug("CheckConfig: arCallTemplate=".$template["arCallTemplate"].", arTemplateId=".$template["arTemplateId"],"object");
					// $arCallTemplate=$this->store->get_config("files")."templates".$arCallTemplate;
					// check if template exists, if it doesn't exist, then continue the original template that called CheckConfig
					$arTemplates=$this->store->get_filestore("templates");
					if ($arTemplates->exists($template["arTemplateId"], $template["arCallTemplate"])) { 
						debug("CheckConfig: ".$template["arCallTemplate"]." exists","object");
						// check if the requested language exists, if not do not display anything, 
						// unless otherwise indicated by $ARCurrent->allnls
						// This triggers only for pinp templates called by other templates,
						// as the first template (in the url) will first trigger the language
						// choice dialogue instead.
						$arLibrary = $template['arLibrary'];
						if (is_int($arLibrary)) {
							// set the library name for unnamed libraries to 'current'
							// so that calls using getvar('arLibrary') will keep on working
							$arLibrary = "current";
						}

						if (!is_string($arCallArgs)) {
							$arCallArgs['arCallFunction'] = $arCallFunction;
							$arCallArgs['arLibrary'] = $arLibrary;
							$arCallArgs['arLibraryPath'] = $template["arLibraryPath"];
						}
						$ARCurrent->arCallStack[]=$arCallArgs;
						// start running a pinp template
						$this->pushContext(
							Array(
								"scope" => "pinp",
								"arLibrary" => $arLibrary,
								"arLibraryPath" => $template['arLibraryPath'],
								"arCallFunction" => $arCallFunction,
								"arCurrentObject" => $this,
								"arCallType" => $template['arCallType'],
								"arCallTemplateType" => $template['arCallTemplateType'],
								"arCallTemplatePath" => $template['arCallTemplatePath'],
								"arLibrariesSeen" => $template['arLibrariesSeen']
							)
						);
						if ($ARCurrent->forcenls || isset($this->data->nls->list[$reqnls])) {
							// the requested language is available.
							$this->nlsdata=$this->data->$reqnls;
							$this->nls=$reqnls;
							$continue=true;
						} else if (!isset($this->data->nls)) {
							debug("pobject: CheckConfig: no nls data ","all");
							// the object has no language support
							$this->nlsdata=$this->data;
							$continue=true;
						} else if (($ARCurrent->allnls) || (!$initialConfigChecked && $ARCurrent->nolangcheck)) {
							debug("pobject: CheckConfig: no $reqnls, but allnls set","all");
							// all objects must be displayed
							// $this->reqnls=$this->nls; // set requested nls, for checks
							$this->nls=$this->data->nls->default;
							$this->nlsdata=$this->data->$nls;
							$continue=true;
						} else {
							// requested language not available, allnls not set
							// -> skip this object (do not run template but do return false)
							$continue=false;
						}
						if ($continue) {
							if ($ARCurrent->ARShowTemplateBorders) {
								echo "<!-- arTemplateStart\nData: ".$this->type." ".$this->path." \nTemplate: ".$template["arCallTemplatePath"]." ".$template["arCallTemplate"]." \nLibrary:".$template["arLibrary"]." -->";

							}
							set_error_handler(array('pobject','pinpErrorHandler'),error_reporting());
							$arResult=$arTemplates->import($template["arTemplateId"], $template["arCallTemplate"], "", $this);
							restore_error_handler();
							if (isset($arResult)) {
								$ARCurrent->arResult=$arResult;
							}
							if ($ARCurrent->ARShowTemplateBorders) {
								echo "<!-- arTemplateEnd -->";
							}

						}
						array_pop($ARCurrent->arCallStack);
						$this->popContext();

						return false;
					} else {
						debug("pobject: CheckConfig: no such file: ".$template["arTemplateId"].$template["arCallTemplate"]."","all");
					}
				} else {
					debug("CheckConfig: no arCallTemplate ($arCallFunction from '$this->path')","object");
				}

			}
		}
		debug("pobject: CheckConfig: end","all");
		return true;
	}
	

	function MkDir($dir) {
		debug("pobject: MkDir($dir)","object");
		return ldMkDir($dir);
	}

	function SetCache($file, $time, $image, $headers) {
		ldSetCache($file, $time, $image, $headers);
	}

	function ClearCache($path="", $private=true, $recurse=false) {
	global $AR;
		debug("pobject: ClearCache($path)","object");
		$norealnode = false;
		if (!$path) { 
			$path=$this->path; 
		} else {
			$realpath = current($this->get($path, "system.get.path.phtml"));
			if($realpath != false) {
				$path = $realpath;
			} else {
				$norealnode = true;
			}
		}
		$recursed = array();

		// filesystem cache image filenames are always lower case, so
		// use special path for that. Remember 'real' path name for
		// recursion and stuff
		$fs_path=strtolower($path);
		$nlslist=$AR->nls->list;
		$nlslist["."]="default";
		$cache_types[] = "normal";
		$cache_types[] = "compressed";
		$cache_types[] = "session";

		$filestore = $this->store->get_config("files");
		foreach($cache_types as $type){
			foreach($nlslist as $nls => $language){
				// break away if nls doesn't exists
				// is dir is cached, so it should not cost more that it add's in speed
				if(!is_dir($filestore."cache/$type/$nls")){
					continue; 
				}

				$fpath=$filestore."cache/$type/$nls".$fs_path;
				$hpath=$filestore."cacheheaders/$type/$nls".$fs_path;
				if ($dir=@dir($fpath)) {
					while (false !== ($entry = $dir->read())) {
						if ($entry!="." && $entry!="..") {
							if (is_file($fpath.$entry)) {
								@unlink($fpath.$entry);
								@unlink($hpath.$entry);
							} else if ( $recurse && !$recursed[$entry]) {
								$this->ClearCache($path.$entry."/", false, true);
								$recursed[$entry]=true;
							}
						}
					}
					$dir->close();
					// remove empty directory entry's, hide errors about directory entry's with content
					@rmdir($fpath);
					@rmdir($hpath);
				} else if (file_exists(substr($fpath,0,-1)."=")) {
					@unlink(substr($fpath,0,-1)."=");
					@unlink(substr($hpath,0,-1)."=");
				}
			}
		}
		if($norealnode === true) {
			/*
				we don't want to recurse to the currentsite, because the path
				doesn't exists in the database, so it doesn't have a currentsite
				
				the privatecache should be emptied by delete, or by the cleanup
				cronjob. The current path doesn't exists in the database, so a
				object id which is needed to find the node in the cache, isn't
				available
			*/
			return; 
		}
		// now clear all parents untill the current site
		$site=$this->currentsite($path);
		if ($path!=$site && $path!='/') {
			$parent=$this->make_path($path.'../');
			$this->ClearCache($parent, $private, false);
		}
		if ($private) {
			// now remove any private cache entries.
			// FIXME: this doesn't scale very well.
			//        only scalable solution is storage in a database
			//        but it will need the original path info to
			//        remove recursively fast enough.
	        //        this means a change in the filestore api. -> 2.5
			$pcache=$this->store->get_filestore("privatecache");
			if ($recurse) {
				$ids=$this->store->info($this->store->find($path, "" ,0));
				if(is_array($ids)){
					foreach($ids as $value) {
						$pcache->purge($value["id"]);
					}
				}
			} else {
				$pcache->purge($this->id);
			}
		}
		debug("pobject: ClearCache: end","all");
	}

	function getcache($name, $nls="") {
		global $ARCurrent;
		$result=false;
		if ($name) {
			$result=false;
			if (!$nls) {
				$nls=$this->nls;
			}
			$file=$nls.".".$name;
			$pcache=$this->store->get_filestore("privatecache");
			if ( $pcache->exists($this->id, $file) &&
			     ($pcache->mtime($this->id, $file)>time()) ) {
				debug("cached: $name exists, reading from cache.","all");
				// FIXME!: should fix links, replace old session id's; use correct root, etc.
				if ($ARCurrent->session) {
					$session="/-".$ARCurrent->session->id."-";
				}
				$result=str_replace("{arSession}", $session, $pcache->read($this->id, $file));
			} else {
				$result=false;
				debug("getcache: $name not found, start new cache image.","all");
				$ARCurrent->cache[]=$file;
				debug("getcache: ob_start()","all");
				ob_start();
				/* output buffering is recursive, so this won't interfere with
				   normal page caching, unless you forget to call savecache()...
				   so normal pagecache needs to check $ARCurrent->cache, if it's
				   not empty, issue a warning and don't cache the outputbuffer...
				   savecache() must then pop the stack. 
				*/
			}
		} else {
			error($ARnls["err:nonamecache"]);
		}
		return $result;
	}

	function cached($name, $nls="") {
		debug("cached($name)","object");
		global $ARCurrent;
		if ($image=$this->getcache($name, $nls)) {
			echo $image;
			$result=true;
		} else {
			$result=false;
		}
		return $result;
	}

	function savecache($time="") {
		debug("savecache($time)","object");
		global $ARCurrent;
		if (!$time) {
			$time=2; // 'freshness' in hours.
		}
		/* FIXME!: change links to current root with a placeholder.
		   problem: make_url makes it possible that the image also
		   contains other 'roots'. Which should be changed to their 
		   corresponding correct session id's.
		   possible fix: only replace the session id with a placeholder:
		   <img src="http://a.host.com/-abcd-/en/a/dir/img.gif"> to
		   <img src="http://a.host.com{arSession}/en/a/dir/img.gif">
		   language gets cached correctly.
           better fix: change this->store->root to {arRoot}, then change
           any remaining session id's to {arSession} ?
		*/
		if (($file=array_pop($ARCurrent->cache)) && $image=ob_get_contents()) {
			if ($ARCurrent->session) {
				$image=str_replace("/-".$ARCurrent->session->id."-","{arSession}",$image);
				$session="/-".$ARCurrent->session->id."-";
			}
			//$path=substr($file, 0, strrpos($file, "/"));
			//if (!file_exists($this->store->get_config("files")."privatecache".$path)) {
			//	ldMkDir("privatecache".$path);
			//}
			//$fp=fopen($this->store->get_config("files")."privatecache".$file, "w");
			//fwrite($fp, $image);
			//fclose($fp);
			$pcache=$this->store->get_filestore("privatecache");
			$pcache->write($image, $this->id, $file);
			$time=time()+($time*3600);
			if (!$pcache->touch($this->id, $file, $time)) {
				debug("savecache: ERROR: couldn't touch $file","object");
			}
			/* it seems that ob_end_flush doesn't really clean the output
			   output buffer, ob_end_clean() does. With flush, the loader
			   keeps thinking there is something to put in the cache while
			   flush also doesn't echo the buffer out... 
			   FIXME: test again in php 4.0.4 
			*/
			ob_end_clean();
			echo str_replace("{arSession}",$session,$image);
			debug("savecache: ob_end_flush()","all");
		} else {
			error($ARnls["err:savecachenofile"]);
		}
		debug("savecache: end. ($file)","all");
	}

	function getdatacache($name) {
		$result=false;
		if ($name) {
			$pcache=$this->store->get_filestore("privatecache");
			if ( $pcache->exists($this->id, $name) &&
			     ($pcache->mtime($this->id, $name)>time()) ) {
				debug("getdatacache: $name exists, reading from cache.","all");
				$result=unserialize($pcache->read($this->id, $name));
			} else {
				debug("getdatacache: $name doesn't exists, returning false.","all");
			}
		} else {
			error($ARnls["err:nonamecache"]);
		}
		return $result;
	}

	function savedatacache($name,$data,$time="") {
		debug("savedatacache($time)","object");
		if (!$time) {
			$time=2; // 'freshness' in hours.
		}
		$pcache=$this->store->get_filestore("privatecache");
		$pcache->write(serialize($data), $this->id, $name);
		$time=time()+($time*3600);
		if (!$pcache->touch($this->id, $name, $time)) {
			debug("savecache: ERROR: couldn't touch $file","object");
		}
		debug("savedatacache: end. ($file)","all");
	}

	function getdata($varname, $nls="none") {
	// function to retrieve variables from $this->data, with the correct
	// language version.
	global $ARCurrent;

		debug("pobject: getdata($varname, $nls)","object");
		$result=false;
		if ($nls!="none") {
			if ($ARCurrent->arCallStack) {
				$arCallArgs=end($ARCurrent->arCallStack);
				if (is_array($arCallArgs)) {
					extract($arCallArgs);
				} else if (is_string($arCallArgs)) {
					Parse_Str($arCallArgs);
				}
			}
			if (isset(${$nls}[$varname])) {
				$result=${$nls}[$varname];
			} else if (isset($ARCurrent->$nls) && isset($ARCurrent->$nls->$varname)) {
				$result=$ARCurrent->$nls->$varname;
			} else if (($values=$_POST[$nls]) && isset($values[$varname])) {
				$result=$values[$varname];
			} else if (($values=$_GET[$nls]) && isset($values[$varname])) {
				$result=$values[$varname];
			} else if (($arStoreVars=$_POST["arStoreVars"]) && isset($arStoreVars[$nls][$varname])) {
				$result=$arStoreVars[$nls][$varname];
			} else if (($arStoreVars=$_GET["arStoreVars"]) && isset($arStoreVars[$nls][$varname])) {
				$result=$arStoreVars[$nls][$varname];
			}
			if ($result===false) {
				if (isset($this->data->${nls}) && isset($this->data->${nls}->${varname})) {
					$result=$this->data->${nls}->${varname};
				} else {
					debug("$varname not found in this->data->$nls", "all");
				}
			}
		} else { // language independant variable.
			if ($ARCurrent->arCallStack) {
				$arCallArgs=end($ARCurrent->arCallStack);
				if (is_array($arCallArgs)) {
					extract($arCallArgs);
				} else if (is_string($arCallArgs)) {
					Parse_Str($arCallArgs);
				}
			}
			if (isset($$varname)) {
				$result=$$varname;
			} else if (isset($ARCurrent->$varname)) {
				$result=$ARCurrent->$varname;
			} else if (isset($_POST[$varname])) {
				$result=$_POST[$varname];
			} else if (isset($_GET[$varname])) {
				$result=$_GET[$varname];
			} else if (($arStoreVars=$_POST["arStoreVars"]) && isset($arStoreVars[$varname])) {
				debug('POST[arStoreVars]','all');
				$result=$arStoreVars[$varname];
			} else if (($arStoreVars=$_GET["arStoreVars"]) && isset($arStoreVars[$varname])) {
				$result=$arStoreVars[$varname];
			}
			if ($result===false) {
				if (isset($this->data->$varname)) {
					$result=$this->data->$varname;
				} else {
					debug("$varname not found in this->data", "all");
				}
			}        
		}
		debug("getdata: end: result=".serialize($result),"all");
		return $result;
	}

	function showdata($varname, $nls="none") {
		debug("pobject: showdata($varname, $nls)","object");
		echo htmlspecialchars($this->getdata($varname, $nls));
	}

	function setnls($nls) {
		debug("pobject: setnls($nls)","object");
		ldSetNls($nls);
	}

	function getcharset() {
		return "UTF-8";
	}

	function HTTPRequest($method, $url, $postdata = "", $port=80 ) { 
		$maxtries = 5;
		$tries = 0;
		$redirecting = true;

		if(is_array($postdata)) { 
			foreach($postdata as $key=>$val) { 
				if(!is_integer($key)) {
					$data .= "$key=".urlencode($val)."&"; 
				}
			} 
		} else { 
			$data = $postdata; 
		}

		while ($redirecting && $tries < $maxtries) {
			$tries++; 
			// get host name and URI from URL, URI not needed though 
			preg_match("/^([htps]*:\/\/)?([^\/]+)(.*)/i", $url, $matches); 
			$host = $matches[2]; 
			$uri = $matches[3]; 
			if (!$matches[1]) {
				$url="http://".$url;
			}
			$connection = @fsockopen( $host, $port, $errno, $errstr, 120); 
			if( $connection ) { 
				if( strtoupper($method) == "GET" ) { 
					if ($data) {
						$uri .= "?" . $data; 
					}
					fputs( $connection, "GET $uri HTTP/1.0\r\n"); 
				} else if( strtoupper($method) == "POST" ) { 
					fputs( $connection, "POST $uri HTTP/1.0\r\n"); 
				} else {
					fputs( $connection, "$method $uri HTTP/1.0\r\n");
				}

				fputs( $connection, "Host: $host\r\n");
				fputs( $connection, "Accept: */*\r\n"); 
				fputs( $connection, "Accept: image/gif\r\n"); 
				fputs( $connection, "Accept: image/x-xbitmap\r\n"); 
				fputs( $connection, "Accept: image/jpeg\r\n"); 

				if( strtoupper($method) == "POST" ) { 
					$strlength = strlen( $data); 
					fputs( $connection, "Content-type: application/x-www-form-urlencoded\r\n" ); 
					fputs( $connection, "Content-length: ".$strlength."\r\n\r\n"); 
					fputs( $connection, $data."\r\n"); 
				} 

				fputs( $connection, "\r\n" , 2); 
				$output = ""; 

				$headerContents = '';
				$headerStart = 0; 
				$headerEnd = 0; 
				$redirecting = false; 

				while (!feof($connection)) { 
					$currentLine = fgets ($connection, 1024); 
					if ($headerEnd && $redirecting) { 
						break; 
					} else if ($headerEnd && !$redirecting) { 
						//this is the html from the page 
						$contents = $contents . $currentLine; 
					} else if ( ereg("^HTTP", $currentLine) ) { 
						//came to the start of the header 
						$headerStart = 1; 
						$headerContents = $currentLine;
					} else if ( $headerStart && preg_match('/^[\n\r\t ]*$/', $currentLine) ) { 
						//came to the end of the header 
						$headerEnd = 1; 
					} else { 
						//this is the header, if you want it... 
						if (preg_match("/^Location: (.+?)\n/is",$currentLine,$matches) ) { 
							$headerContents .= $currentLine;
							//redirects are sometimes relative 
							$newurl = $matches[1]; 
							if (!preg_match("/http:\/\//i", $newurl, $matches) ) { 
								$url .= $newurl; 
							} else { 
								$url = $newurl; 
							} 
							//extra \r's get picked up sometimes 
							//i think only with relative redirects 
							//this is a quick fix. 
							$url = preg_replace("/\r/s","",$url); 
							$redirecting = true; 
						} else {
							$headerContents.=$currentLine;
						}
					} 
				} 
			} else {
				$this->error="$errstr ($errno)";
				$contents=false;
				$url = "";
			}
			@fclose($connection); 
		}
		if (($method!="GET") && ($method!="POST")) {
			$contents=$headerContents."\n".$contents;
		}
		return $contents; 
	} 

	function make_filesize( $size="" ,$precision=0) {
		$result = "0";
		$suffixes = array('B','KB','MB','GB','TB','PB','EB','ZB','YB');

		if( $size === "" ) {
			$size = $this->size;
		}
		while ( (count($suffixes) > 1) && ($size > 1024) ){
			$size = $size / 1024;
			array_shift($suffixes);
		}
		$size = round($size,$precision);
		if($precision==0){ // compatible with the old make_filesize
			$size = intval($size);
		}
		$result = $size." ".array_shift($suffixes);
		return $result;
	}

	function convertToUTF8($data, $charset = "CP1252") {

		include_once($this->store->get_config("code")."modules/mod_unicode.php");

		if (is_array($data)) {
			foreach($data as $key => $val){
				$data[$key] = $this->convertToUTF8($val, $charset);
			}
		} else
		if (is_object($data)) {
			foreach($data as $key => $val){
				$data->$key = $this->convertToUTF8($val, $charset);
			}
		} else {
			$data = unicode::convertToUTF8($charset, $data);
		}
		return $data;
	}

	function resetloopcheck() {
		global $ARBeenHere;
		$ARBeenHere=Array();
	}

/********************************************************************

  "safe" functions.

  The following functions are safe versions of existing functions
  above. 
  - They don't change anything in the database. 
    This means that to save/delete something, a user will need to call
    "system.save.data.phtml" or "system.delete.phtml" which check grants.
  - All functions except _get and _exists don't take a path as 
    argument, they use the current objects path instead.

  These are meant to be used by 'pinp' versions of templates,
  meaning user defined templates. 'pinp' rewrites call to functions
  to the form '$this->_function'.

  All pinp files automatically first call CheckLogin('read').
  
********************************************************************/

	function _call($function, $args="") {
		// remove possible path information (greedy match)
		$function=basename($function);
		return $this->call($function, $args);
	}

	function _call_super($arCallArgs="") {
	global $ARCurrent;
		$context = $this->getContext();
		if (!$arCallArgs) {
			$arCallArgs = current($ARCurrent->arCallStack);
		}
		$arCallFunction = $context['arCallFunction'];
		$arCallType = $context['arCallTemplateType'];
		$arSuperPath = $context['arCallTemplatePath'];
		$arLibrariesSeen = $context['arLibrariesSeen'];

		// remove current library path from the arLibrarieSeen array so that
		// Ariadne will be able to re-enter the library and toggle the arSuperContext boolean there.
		// 
		// FIXME(!!): We probably should remember the whole library-entry tree (in case we entered a library
		// from within anothor library) so that we can also 'unlock' all the previous libraries.
		//
		unset($arLibrariesSeen[$arSuperPath]);
		$arSuperContext = Array(
			'path' => $arSuperPath,
			'type' => $arCallType,
			'function' => $arCallFunction
		);
		debug("call_super: searching for the template following (path: $arSuperPath; type: $arCallType; function: $arCallFunction) from $this->path");
		$template = $this->getPinpTemplate($arCallFunction, $this->path, '', false, $arLibrariesSeen, &$arSuperContext);
		if ($template["arCallTemplate"] && $template["arTemplateId"]) {
			debug("call_super: template $arCallType::$arCallFunction found and calling it");
			$arTemplates=$this->store->get_filestore("templates");
			if ($arTemplates->exists($template["arTemplateId"], $template["arCallTemplate"])) { 
				debug("call_super: ".$template["arCallTemplate"]." exists");
				$arLibrary = $template['arLibrary'];
				if (is_int($arLibrary)) {
					// set the library name for unnamed libraries to 'current'
					// so that calls using getvar('arLibrary') will keep on working
					$arLibrary = "current";
				}
				if (!is_string($arCallArgs)) {
					$arCallArgs['arCallFunction'] = $arCallFunction;
					$arCallArgs['arLibrary'] = $arLibrary;
					$arCallArgs['arLibraryPath'] = $template["arLibraryPath"];
				}
				$ARCurrent->arCallStack[]=$arCallArgs;
				$this->pushContext(
					Array(
						"scope" => "pinp",
						"arLibrary" => $arLibrary,
						"arLibraryPath" => $template['arLibraryPath'],
						"arCallFunction" => $arCallFunction,
						"arCallType" => $template['arCallType'],
						"arCallTemplateType" => $template['arCallTemplateType'],
						"arCallTemplatePath" => $template['arCallTemplatePath']
					)
				);
				set_error_handler(array('pobject','pinpErrorHandler'),error_reporting());
				$arResult = $arTemplates->import($template["arTemplateId"], $template["arCallTemplate"], "", $this);
				restore_error_handler();

				array_pop($ARCurrent->arCallStack);
				$this->popContext();
			}
		} else {
			debug("call_super: no success finding $arCallType::$arCallFunction");
		}

		return $arResult;
	}

	function _get($path, $function="view.html", $args="") {
		debug("pobject: get($path, $function, $args)","object");
		// remove possible path information (greedy match)
		$function=basename($function);
		return $this->store->call($function, $args, 
			$this->store->get(
				$this->make_path($path))); 
	}

	function _call_object($object, $function, $args="") {
		return $object->call($function, $args);
	}

	function _ls($function="list.html", $args="") {
		debug("pobject: ls($function, $args)","object");
		// remove possible path information (greedy match)
		$function=basename($function);
		return $this->store->call($function, $args, 
			$this->store->ls($this->path));
	}

	function _parents($function="list.html", $args="", $top="") {
		debug("pobject: parents($function, $args)","object");
		// remove possible path information (greedy match)
		$function=basename($function);
		return $this->parents($this->path, $function, $args, $top);
	}

	function _find($criteria, $function="list.html", $args="", $limit=100, $offset=0) {
		// remove possible path information (greedy match)
		$function=basename($function);
		$result = $this->store->call($function, $args, 
			$this->store->find($this->path, $criteria, $limit, $offset));
		if ($this->store->error) {
			$this->error = $store->store->error;
		}
		return $result;
	}

	function _exists($path) {
		debug("pobject: exists($path)","object");
		return $this->store->exists($this->make_path($path));
	}

	function _implements($implements) {
		return $this->AR_implements($implements);
	}

	function getvar($var) {
	global $ARCurrent, $ARConfig;

		debug("pobject: getvar($var)","object");
		if ($ARCurrent->arCallStack) {
			$arCallArgs=end($ARCurrent->arCallStack);
			if (is_array($arCallArgs)) {
				extract($arCallArgs);
			} else if (is_string($arCallArgs)) {
				Parse_Str($arCallArgs);
			}
		}
		if (isset($$var)) {
			$result=$$var;
		} else if (isset($ARCurrent->$var)) {
			$result=$ARCurrent->$var;
		} else if (isset($ARConfig->pinpcache[$this->path][$var])) {
			$result=$ARConfig->pinpcache[$this->path][$var];
		} else if (isset($_POST[$var])) {
			$result=$_POST[$var];
		} else if (isset($_GET[$var])) {
			$result=$_GET[$var];
		} else if (($arStoreVars=$_POST["arStoreVars"]) && isset($arStoreVars[$var])) {
			$result=$arStoreVars[$var];
		} else if (($arStoreVars=$_GET["arStoreVars"]) && isset($arStoreVars[$var])) {
			$result=$arStoreVars[$var];
		} 
		return $result;
	}

	function _getvar($var) {
		return $this->getvar($var);
	}

	function putvar($var, $value) {
		global $ARCurrent;

		debug("pobject: putvar($var, ".serialize($value).")","object");
		$ARCurrent->$var=$value;
	}  

	function _putvar($var, $value) {
		return $this->putvar($var, $value);
	}

	function _setnls($nls) {
		$this->setnls($nls);
	}
	
	// not exposed to pinp for obvious reasons
	function suKey($grants) {
		global $AR;
		if( !$AR->suSalt || !$this->CheckSilent("config") ) {
			return false;
		}
		// serialize the grants so the order does not matter, mod_grant takes care of the sorting for us
		$this->_load("mod_grant.php");
		$mg = new mod_grant();
		$grantsarray = array();
		$mg->compile($grants, $grantsarray);
		$grants = serialize($grantsarray);
		return sha1( $AR->suSalt . $grants . $this->path);
	}
	
	function suBegin($grants, $key) {
		global $AR;
		$result = false;

		// serialize the grants so the order does not matter, mod_grant takes care of the sorting for us
		$this->_load("mod_grant.php");
		$mg = new mod_grant();
		$grantsarray = array();
		$mg->compile($grants, $grantsarray);
		$checkgrants = serialize($grantsarray);
		$check = ( $AR->suSalt ? sha1( $AR->suSalt . $checkgrants . $this->path) : false ); // not using suKey because that checks for config grant
		if( $check !== false && $check === $key ) {
			unset($AR->user->grants[$this->path]); // this makes sure GetValidGrants is called again upon a grant check
			$grantsarray = (array)$AR->suGrants[$this->path];
			$mg->compile($grants, $grantsarray);
			$AR->suGrants[$this->path] = $grantsarray;
			$result = true;
		}
		return $result;
	}
	
	function suEnd() {
		global $AR;
		unset($AR->user->grants[$this->path]); // this makes sure GetValidGrants is called again upon a grant check
		unset($AR->suGrants[$this->path]);
		return true; // temp return true;
	}
	
	function suCall($grants, $key, $function, $args) {
		$result = false;
		if( $this->suBegin($grants, $key ) ) {
			$result = $this->call($function, $args);
			$this->suEnd();
		}
		return $result;
	}
	
	function _suBegin($grants, $key) {
		return $this->suBegin($grants, $key);
	}
	
	function _suEnd() {
		return $this->suEnd();
	}
	
	function _suCall($grants, $key, $function, $args) {
		return $this->suCall($grants, $key, $function, $args);
	}

	function _widget($arWidgetName, $arWidgetTemplate, $arWidgetArgs="", $arWidgetType="lib") {
	global $AR, $ARConfig, $ARCurrent, $ARnls;

		debug("pobject: widget($arWidgetName, $arWidgetTemplate, $arWidgetArgs)","object");
		$arWidgetName=ereg_replace("[^a-zA-Z0-9\/]","",$arWidgetName);
		$arWidgetTemplate=ereg_replace("[^a-zA-Z0-9\.]","",$arWidgetTemplate);
		if ($arWidgetType=="www") {
			$coderoot=$AR->dir->root;
		} else {
			$coderoot=$this->store->get_config("code");
		}
		if (file_exists($coderoot."widgets/$arWidgetName")) {
			if (file_exists($coderoot."widgets/$arWidgetName/$arWidgetTemplate")) {
				if (is_array($arWidgetArgs)) {
					extract($arWidgetArgs);
				} else if (is_string($arWidgetArgs)) {
					Parse_str($arWidgetArgs);
				}
				include($coderoot."widgets/$arWidgetName/$arWidgetTemplate");
			} else {
				error("Template $arWidgetTemplate for widget $arWidgetName not found.");
			}
		} else {
			error(sprintf($ARnls["err:widgetnotfound"],$wgName));
		}
		if ($wgResult) {
			return $wgResult;
		}
	}

	function _getdata($varname, $nls="none") { 
		return $this->getdata($varname, $nls);
	}

	function _showdata($varname, $nls="none") {
		$this->showdata($varname, $nls);
	}

	function _gettext($index=false) {
	global $ARnls, $ARCurrent;
		if (!$index) {
			return array_merge($ARnls, $ARCurrent->ARnls ? $ARCurrent->ARnls : array(), $this->ARnls ? $this->ARnls : array());
		} else {
			if (isset($this->ARnls[$index])) {
				return $this->ARnls[$index];
			} else if (isset($ARCurrent->ARnls[$index])) {
				return $ARCurrent->ARnls[$index];
			} else if (isset($ARnls[$index])) {
				return $ARnls[$index];
			} else {
				return "{" . $index . "}";
			}
		}
	}

	function _loadtext($nls, $section="") {
		global $ARCurrent;

		$nls=eregi_replace('[^a-z]*','',$nls);
		$section=eregi_replace('[^a-z\._:-]*','',$section);
		if (!$section) {
			include($this->store->get_config("code")."nls/".$nls);
			$this->ARnls = array_merge((array)$this->ARnls, $ARnls);
		} else {
			$nlsfile = $this->store->get_config("code")."nls/".$section.".".$nls;
			if(strpos($nlsfile, ':') === false && file_exists($nlsfile)) {
				include($nlsfile);
				$this->ARnls = array_merge((array)$this->ARnls, $ARnls);
			} else {
				// current result;
				$arResult = $ARCurrent->arResult;
				$this->pushContext(Array());
					$this->CheckConfig($section, Array('nls' => $nls));
				$this->popContext();
				// reset current result (CheckConfig may have changed it when it should not have).
				$ARCurrent->arResult = $arResult;
			}
		}
	}

	function _startsession() {
	global $ARCurrent;
		ldStartSession(0);
		return $ARCurrent->session->id;
	}

	function _putsessionvar($varname, $varvalue) {
	global $ARCurrent;

		if ($ARCurrent->session) {
			return $ARCurrent->session->put($varname, $varvalue);
		} else {
			return false;
		}
	}

	function _getsessionvar($varname) {
	global $ARCurrent;

		if ($ARCurrent->session) {
			return $ARCurrent->session->get($varname);
		} else {
			return false;
		}
	}

	function _killsession() {
	global $ARCurrent;

		if ($ARCurrent->session) {
			$ARCurrent->session->kill();
			unset($ARCurrent->session);
		}
	}

	function _sessionid() {
	global $ARCurrent;
		if ($ARCurrent->session) {
			return $ARCurrent->session->id;
		} else {
			return 0;
		}
	}

	function _resetloopcheck() {
		return $this->resetloopcheck();
	}

	function _make_path($path="") {
		return $this->make_path($path);
	}

	function _make_url($path="", $nls=false, $session=true, $https=-1, $keephost=false) {
		return $this->make_url($path, $nls, $session, $https, $keephost);
	}

	function _make_local_url($path="", $nls="", $session=true, $https=-1) {
		return $this->make_local_url($path, $nls, $session, $https);
	}

	function _getcache($name, $nls='') {
		return $this->getcache($name, $nls);
	}

	function _cached($name, $nls='') {
		return $this->cached($name, $nls);
	}

	function _savecache($time="") {
		return $this->savecache($time);
	}

	function _getdatacache($name) {
		return $this->getdatacache($name);
	}

	function _savedatacache($name,$data,$time="")
	{
		return $this->savedatacache($name,$data,$time);
	}

	function currentsite($path="") {
		global $ARCurrent;
		if (!$path) {
			$path=$this->path;
		}
		$config=$this->loadConfig($path);
		if (@count($ARCurrent->shortcut_redirect)) {
			$redir = end($ARCurrent->shortcut_redirect);
			if ($redir["keepurl"] && substr($path, 0, strlen($redir["dest"])) == $redir["dest"]) {
				if (substr($config->site, 0, strlen($redir["dest"]))!=$redir["dest"]) {
					// search currentsite from the reference
					$config = $this->loadConfig($redir['src']);
				}
			}
		}
		return $config->site;
	}

	function parentsite($site) {
		$path=$this->store->make_path($site, "..");
		$config=$this->loadConfig($path);
		return $config->site;
	}

	function currentsection($path="") {
		if (!$path) {
			$path=$this->path;
		}
		$config=$this->loadConfig($path);
		return $config->section;
	}

	function parentsection($section) {
		$path=$this->store->make_path($section, "..");
		$config=$this->loadConfig($path); 
		return $config->section;
	}

	function getValue($name, $nls=false) {
	global $ARCurrent;
		switch ($nls) {
			case "none":
				$result = $this->data->$name;
			break;
			case false:
				$nls = $ARCurrent->nls;
				if (!isset($this->data->$nls) || !isset($this->data->$nls->$name)) {
					$result = $this->data->$name;
					break;
				}
			default:
				$result = $this->data->$nls->$name;
		}
		return $result;
	}

	function setValue($name, $value, $nls=false) {
	global $AR, $ARConfig;
		if ($value === NULL) {
			if ($nls && $nls!="none") {
				unset($this->data->$nls->$name);
				if (!count(get_object_vars($this->data->$nls))) {
					unset($this->data->$nls);
					unset($this->data->nls->list[$nls]);
					if (!count($this->data->nls->list)) {
						unset($this->data->nls->list);
						unset($this->data->nls);
					} else {
						if ($this->data->nls->default == $nls) {
							if ($this->data->nls->list[$ARConfig->nls->default]) {
								$this->data->nls->default = $ARConfig->nls->default;
							} else {
								list($this->data->nls->default) = each($this->data->nls->list);
							}
						}
					}
				}
			} else {
				unset($this->data->$name);
			}
		} else
		if (!$nls) {
			$this->data->$name = $value;
		} else {
			if (!$this->data->$nls) {
				$this->data->$nls = new object;
				if (!$this->data->nls) {
					$this->data->nls = new object;
					$this->data->nls->default = $nls;
				}
				$this->data->nls->list[$nls] = $AR->nls->list[$nls];
			}
			$this->data->$nls->$name = $value;
		}
	}

	function showValue($name, $nls=false) {
		$result = $this->getValue($name, $nls);
		echo $result;
		return $result;
	}

	function _getValue($name, $nls=false) {
		return $this->getValue($name, $nls);
	}

	function _setValue($name, $value, $nls=false) {
		return $this->setValue($name, $value, $nls);
	}

	function _showValue($name, $nls=false) {
		return $this->showValue($name, $nls);
	}

	function _currentsite() {
		return $this->currentsite();
	}

	function _parentsite($site) {
		return $this->parentsite($site);
	}

	function _currentsection() {
		return $this->currentsection();
	}

	function _parentsection($section) {
		return $this->parentsection($section);
	}

	function _checkgrant($grant, $modifier=ARTHISTYPE, $path=".") {
		// as this is called within a pinp template, 
		// all the grants are already loaded, so
		// checksilent will fullfill our needs
		$this->pushContext(Array("scope" => "php"));
			$result = $this->CheckSilent($grant, $modifier, $path);
		$this->popContext();
		return $result;
	}

	function _checkpublic($grant, $modifier=ARTHISTYPE) {

		return $this->CheckPublic($grant, $modifier);
	}

	function _getcharset() {
		return $this->getcharset();
	}

	function _count_find($query='') {
		return $this->count_find($this->path, $query);
	}

	function _count_ls() {
		return $this->count_ls($this->path);
	}

	function _HTTPRequest($method, $url, $postdata = "", $port=80) {
		return $this->HTTPRequest($method, $url, $postdata, $port);
	}
	
	function _make_filesize( $size="" ,$precision=0) {
		return $this->make_filesize( $size ,$precision);
	}

	function _convertToUTF8($data, $charset = "CP1252") {
		return $this->convertToUTF8($data,$charset);
	}

	function _getuser() {
		global $AR;
		$user=$AR->user;
		unset($user->grants);
		unset($user->groups);
		return clone($user);
	}
	
	function ARinclude($file) {
		debug('including file ['.$file.'] in the current scope','all');
		include($file);
	}

	function _load($class) {
		// only allow access to modules in the modules directory.
		$class=eregi_replace('[^a-z0-9\._]','',$class);
		include_once($this->store->get_config("code")."modules/".$class);
	}

	function _import($class) {
		// deprecated
		return $this->_load($class);
	}

	function html_to_text($text) {
		$trans = array_flip(get_html_translation_table(HTML_ENTITIES));
		//strip nonbreaking space, strip script and style blocks, strip html tags, convert html entites, strip extra white space
		$search_clean = array("%&nbsp;%i", "%<(script|style)[^>]*>.*?<\/(script|style)[^>]*>%si", "%<[\/]*[^<>]*>%Usi", "%(\&[a-zA-Z0-9\#]+;)%es", "%\s+%");
		$replace_clean = array(" ", " ", " ", "strtr('\\1',\$trans)", " ");
		return preg_replace($search_clean, $replace_clean, $text);
	}

	function _html_to_text($text) {
		return $this->html_to_text($text);
	}

	function _newobject($filename, $type) {
		$newpath=$this->make_path($filename);
		$newparent=$this->store->make_path($newpath, "..");
		$data=new object;
		$object=$this->store->newobject($newpath, $newparent, $type, $data);
		$object->arIsNewObject=true;
		return $object;
	}

	function _save($properties="", $vtype="") {
		if ($this->arIsNewObject && $this->CheckSilent('add', $this->type)) {
			unset($this->data->config);
			$result = $this->save($properties, $vtype);
		} else if (!$this->arIsNewObject && $this->CheckSilent('edit', $this->type)) {
			$this->data->config = current($this->get('.', 'system.get.data.config.phtml'));
			$result = $this->save($properties, $vtype);
		}
		return $result;
	}

	function _is_supported($feature) {
		return $this->store->is_supported($feature);
	}

	/*
		since the preg_replace() function is able to execute normal php code
		we have to intercept all preg_replace() calls and parse the
		php code with the pinp parser.
	*/


	/*	this is a private function used by the _preg_replace wrapper */

	function preg_replace_compile($pattern, $replacement) {
		include_once($this->store->get_config("code")."modules/mod_pinp.phtml");
		ereg("^\s*(.)", $pattern, $regs);
		$delim = $regs[1];
		if (eregi($k="${delim}[^$delim]*e[^$delim]*".'$', $pattern)) {
			$pinp = new pinp("header", 'local->', '$AR_this->_');
			return substr($pinp->compile("<pinp>$replacement</pinp>"), 5, -2);
		} else {
			return $replacement;
		}
	}
 
	function _preg_replace($pattern, $replacement, $text, $limit = -1) {
		if (is_array($pattern)) {
			$newrepl = array();
			reset($replacement);
			foreach ($pattern as $i_pattern) {
				list(, $i_replacement) = each($replacement);
				$newrepl[] = $this->preg_replace_compile($i_pattern, $i_replacement);
			}
		} else {
			$newrepl = $this->preg_replace_compile($pattern, $replacement);
		}
		return preg_replace($pattern, $newrepl, $text, $limit);
	}

	function _loadConfig($path='') {
		return clone $this->loadConfig($path);
	}

	function _loadUserConfig($path='') {
		return $this->loadUserConfig($path);
	}

	function _loadLibrary($name, $path) {
		return $this->loadLibrary($name, $path);
	}

	function _getSetting($setting) {
	global $AR;

		switch ($setting) {
			case 'www':
			case 'dir:www':
				return $AR->dir->www;
		}
	}

	function __call($name,$arguments) {
		switch($name) {
			case "implements":
				return $this->AR_implements($arguments[0]);
			break;
			default:
				trigger_error(sprintf('Call to undefined function: %s::%s().', get_class($this), $name), E_USER_ERROR);
				return false;
		}
	}

	function pinpErrorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
		global $AR,$nocache,$ARCurrent;
		if (($errno & error_reporting()) == 0) {
			return true;
		}

		$nocache = true;
		$item = pobject::getContext();
		if ($item["arLibraryPath"]) { //  != NULL) {
			echo "Error on line $errline in ".$item['arCallFunction'] ." in library ".$item["arLibraryPath"] ."\n<br>";
			echo $errstr."\n<br>";
		} else {
			echo "Error on line $errline in ".$item['arCallFunction'] ." on object ".$errcontext['AR_this']->path."\n<br>";
			echo $errstr."\n<br>";
		}

		return true;
	}

} // end of ariadne_object class definition
?>