<? $this->render('form_top'); ?>

<div class="title">
	<?= $mode === 'edit' ? 'Edit' : 'Add' ?> Script
</div>

<? if ($mode === 'edit'): ?>
<form method="post" action="">
<? if (!empty($scripts) || isset($selected_theme_id)): ?>
<div class="form-area">
<? if (!empty($scripts)): ?>
	Editing script <select name="selected_script_id">
	<? foreach($scripts as $id=>$name): ?>
		<option value="<?= $id ?>" <?= ($id == $selected_script_id) ? 'selected="selected"' : '' ?>><?= $this->escape($name) ?></option>
	<? endforeach; ?>
	</select>
	<? if (isset($selected_theme_id)): ?>
		from theme
	<? endif; ?>
<? elseif (isset($selected_theme_id)): ?>
	Theme: 
<? endif; ?>
	<? if (isset($selected_theme_id)): ?>
		<? $this->render('design/theme_selector'); ?>
	<? endif; ?>
	<input type="submit" name="go" value="Switch" />
</div>
<? endif; ?>
</form>
<? endif; ?>

<? if (isset($script)): ?>
<? $scriptName = (!empty($selected_theme_id) ? ($this->escape(trim($themes[$selected_theme_id])).':') : '') . $this->escape($script->slug); ?>
<form method="post" action="">
	<input type="hidden" name="selected_theme_id" value="<?= @$selected_theme_id ?>" />
	<input type="hidden" name="selected_script_id" value="<?= @$selected_script_id ?>" />
	<div class="form-area">
		<fieldset>
<? if ($mode === 'add'): ?>
			<div class="field collapse">
				<label<?= isset($errors['script_name']) ? ' class="error"' : '' ?> for="script_name">Adding script</label>
				<input type="text" id="script_name" name="script_name" value="<?= $this->escape($script->slug) ?>" />
				<? if (isset($selected_theme_id)): ?>
					for theme <? $this->render('design/theme_selector'); ?>
				<? endif; ?>
				<?= isset($errors['script_name']) ? "<div class=\"error\">{$this->escape($errors['script_name'])}</div>" : '' ?>
			</div>
<? endif; ?>
<? $this->render('metadata_builder', array('prefix'=>'script', 'titles'=>array('content_type'=>'Content-Type', 'url'=>'Override URL'), 'metadata'=>array('content_type'=>$script->ctype,'url'=>$script->url))); ?>
			<div class="field collapse">
				<label class="title<?= isset($errors["script_content"]) ? ' error' : '' ?>" for="script_content"><a href="">Body</a></label>
				<div class="collapsible">
					<textarea id="script_content" class="code" name="script_content" rows="3" cols="80"><?= $this->escape($script->content) ?></textarea>
					<?= isset($errors['script_content']) ? "<div class=\"clear error\">{$this->escape($errors['script_content'])}</div>" : '' ?>
				</div>
			</div>
		</fieldset>
<? if ($mode === 'edit'): ?>
		<p class="status">Last updated by <?= $script->editor_name ?> at <?= $script->edited('h:i A T') ?> on <?= $script->edited('F d, Y') ?></p>
<? endif; ?>
	</div>
	<div class="buttons">
<? if ($showButtons = ($can_save || ($mode === 'edit' && $can_delete))): ?>
<? if ($can_save): ?>
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save <?= ($mode === 'add') ? 'Script' : 'Changes' ?> 
		</button>
<? endif; ?>
<? if ($mode === 'edit' && $can_delete): ?>
		<a class="negative" href="<?= $this->urlTo('/design/scripts/delete/'.$selected_script_id) ?>">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Script &ldquo;<?= $scriptName ?>&rdquo;
		</a>
<? endif; ?>
<? endif; ?>
	</div>
<? if ($mode === 'edit' && $can_add): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/design/scripts/add') ?>">Add New Script</a>
<? elseif ($mode === 'add' && $can_edit): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/design/scripts/edit') ?>">Edit Existing Script</a>
<? endif; ?>
</form>
<? elseif ($can_add): ?>
<div class="buttons">
	<a class="positive" href="<?= $this->urlTo('/design/scripts/add') ?>">Add New Script</a>
</div>
<? endif; ?>
<div class="clear"></div>
