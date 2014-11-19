<?php
	$ARCurrent->nolangcheck=true;
	if ($this->CheckSilent("read") && $this->CheckConfig()) {
		$arLanguage=$this->getdata("arLanguage","none");
		if (!$arLanguage) {
			$arLanguage=$ARConfig->nls->default;
		}
		$selectednls=$arLanguage;
		$selectedlanguage=$ARConfig->nls->list[$arLanguage];

		$flagurl = $AR->dir->images."nls/small/$selectednls.gif";

		if (!$wgBrowsePath=$this->getdata("path","none")) {
			$wgBrowsePath=$this->parent;
		}
	?>
<script type="text/javascript">
	function callback(path) {
		document.getElementById("path").value = path;
	}
</script>
<fieldset id="data">
	<legend><?php echo $ARnls["data"]; ?></legend>
	<div class="field">
		<label for="name" class="required"><?php echo $ARnls["name"]; ?></label>
		<img class="flag" src="<?php echo $flagurl; ?>" alt="<?php echo $selectedlanguage; ?>">
		<input id="name" type="text" name="<?php echo $selectednls."[name]"; ?>"
			value="<?php $this->showdata("name", $selectednls); ?>" class="inputline wgWizAutoFocus">
	</div>
	<div class="field">
		<label for="summary"><?php echo $ARnls["summary"]; ?></label>
		<img class="flag" src="<?php echo $flagurl; ?>" alt="<?php echo $selectedlanguage; ?>">
		<textarea id="summary" name="<?php echo $selectednls."[summary]"; ?>" class="inputbox" rows="5" cols="42"><?php
			echo $this->showdata("summary", $selectednls);
		?></textarea>
	</div>
</fieldset>
<fieldset id="target" class="browse">
	<legend><?php echo $ARnls["target"]; ?></legend>
	<div class="field">
		<label for="path" class="required"><?php echo $ARnls["path"]; ?></label>
		<input type="text" id="path" name="path" value="<?php echo $wgBrowsePath; ?>" class="inputline">
		<input class="button" type="button" value="<?php echo $ARnls['browse']; ?>" title="<?php echo $ARnls['browse']; ?>" onclick='window.open("<?php echo $this->make_ariadne_url($wgBrowsePath); ?>" + "dialog.browse.php", "browse", "height=480,width=750"); return false;'>
	</div>
	<div class="field">
		<label for="keepurl"><?php echo $ARnls["keepshortcuturl"]; ?></label>
		<select name="keepurl">
			<option value="" <?php if (!$this->getdata("keepurl")) echo "selected"; ?>>No</option>
			<option value="1" <?php if ($this->getdata("keepurl")) echo "selected"; ?>>Yes</option>
		</select>
	</div>
</fieldset>
<?php
	}
?>
