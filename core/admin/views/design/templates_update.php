<? $this->render('form_top'); ?>

<div class="title">
	<?= $mode === 'edit' ? 'Edit' : 'Add' ?> Template
</div>

<? if ($mode === 'edit'): ?>
<form method="post" action="">
<? if (!empty($templates) || isset($selected_theme_id)): ?>
<div class="form-area">
<? if (!empty($templates)): ?>
	Editing template <select name="selected_template_id">
	<? foreach($templates as $id=>$name): ?>
		<option value="<?= $id ?>"<?= ($id == $selected_template_id) ? ' selected="selected"' : '' ?>><?= $this->escape($name) ?></option>
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
	in branch
	<? $this->render('design/branch_selector'); ?>
	<input type="submit" name="go" value="Switch" />
</div>
<? endif; ?>
</form>
<? endif; ?>

<? if (isset($template)): ?>
<? $templateName = (!empty($selected_theme_id) ? ($this->escape(trim($themes[$selected_theme_id])).':') : '') . $this->escape($template->name); ?>
<form method="post" action="">
	<input type="hidden" name="selected_theme_id" value="<?= @$selected_theme_id ?>" />
	<input type="hidden" name="selected_template_id" value="<?= @$selected_template_id ?>" />
	<div class="form-area">
		<fieldset>
<? if ($mode === 'add'): ?>
			<div class="field collapse">
				<label<?= isset($errors['template_name']) ? ' class="error"' : '' ?> for="template_name">Adding template</label>
				<input type="text" id="template_name" name="template_name" value="<?= $this->escape($template->name) ?>" />
				<? if (isset($selected_theme_id)): ?>
					for theme <? $this->render('design/theme_selector'); ?>
				<? endif; ?>
				in branch
				<? $this->render('design/branch_selector'); ?>
				<?= isset($errors['template_name']) ? "<div class=\"error\">{$this->escape($errors['template_name'])}</div>" : '' ?>
			</div>
<? endif; ?>
<? $this->render('metadata_builder', array('collapsed'=>true, 'titles'=>array('content_type'=>'Content-Type', 'url'=>'Override URL'), 'metadata'=>array('template'=>array('content_type'=>$template->ctype)))); ?>
			<div class="field collapse">
				<label class="title<?= isset($errors["template_content"]) ? ' error' : '' ?>" for="template_content"><a href="">Body</a></label>
				<div class="collapsible">
					<textarea id="template_content" class="code" name="template_content" rows="3" cols="80"><?= $this->escape($template->content) ?></textarea>
					<?= isset($errors['template_content']) ? "<div class=\"clear error\">{$this->escape($errors['template_content'])}</div>" : '' ?>
				</div>
			</div>
		</fieldset>
<? if ($mode === 'edit'): ?>
		<p class="status">Last updated by <?= $template->editor_name ?> at <?= $template->edited('h:i A T') ?> on <?= $template->edited('F d, Y') ?></p>
<? endif; ?>
	</div>
	<div class="buttons">
<? if ($showButtons = ($can_save || ($mode === 'edit' && $can_delete))): ?>
<? if ($can_save): ?>
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save <?= ($mode === 'add') ? 'Template' : 'Changes' ?> 
		</button>
<? endif; ?>
<? if ($mode === 'edit' && $can_delete): ?>
		<a class="negative" href="<?= $this->urlTo('/design/templates/delete/'.$selected_template_id) ?>">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Template &ldquo;<?= $templateName ?>&rdquo;
		</a>
<? endif; ?>
<? endif; ?>
	</div>
<? if ($mode === 'edit' && $can_add): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/design/templates/add') ?>">Add New Template</a>
<? elseif ($mode === 'add' && $can_edit): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/design/templates/edit') ?>">Edit Existing Template</a>
<? endif; ?>
</form>
<? elseif ($can_add): ?>
<div class="buttons">
	<a class="positive" href="<?= $this->urlTo('/design/templates/add') ?>">Add New Template</a>
</div>
<? endif; ?>
<div class="clear"></div>
