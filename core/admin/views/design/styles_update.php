<? $this->render('form_top'); ?>

<div class="title">
	<?= $mode === 'edit' ? 'Edit' : 'Add' ?> Style
</div>

<? if ($mode === 'edit'): ?>
<form method="post" action="">
<? if (!empty($styles) || isset($selected_theme_id)): ?>
<div class="form-area">
<? if (!empty($styles)): ?>
	Editing style <select name="selected_style_id">
	<? foreach($styles as $id=>$name): ?>
		<option value="<?= $id ?>" <?= ($id == $selected_style_id) ? 'selected="selected"' : '' ?>><?= $this->escape($name) ?></option>
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

<? if (isset($style)): ?>
<? $styleName = (!empty($selected_theme_id) ? ($this->escape(trim($themes[$selected_theme_id])).':') : '') . $this->escape($style->slug); ?>
<form method="post" action="">
	<input type="hidden" name="selected_theme_id" value="<?= @$selected_theme_id ?>" />
	<input type="hidden" name="selected_style_id" value="<?= @$selected_style_id ?>" />
	<div class="form-area">
		<fieldset>
<? if ($mode === 'add'): ?>
			<div class="field collapse">
				<label<?= isset($errors['style_name']) ? ' class="error"' : '' ?> for="style_name">Adding style</label>
				<input type="text" id="style_name" name="style_name" value="<?= $this->escape($style->slug) ?>" />
				<? if (isset($selected_theme_id)): ?>
					for theme <? $this->render('design/theme_selector'); ?>
				<? endif; ?>
				<?= isset($errors['style_name']) ? "<div class=\"error\">{$this->escape($errors['style_name'])}</div>" : '' ?>
			</div>
<? endif; ?>
<? $this->render('metadata_builder', array('titles'=>array('content_type'=>'Content-Type', 'url'=>'Override URL'), 'metadata'=>array('style'=>array('content_type'=>$style->ctype,'url'=>$style->url)))); ?>
			<div class="field collapse">
				<label class="title<?= isset($errors["style_content"]) ? ' error' : '' ?>" for="style_content"><a href="">Body</a></label>
				<div class="collapsible">
					<textarea id="style_content" class="code" name="style_content" rows="3" cols="80"><?= $this->escape($style->content) ?></textarea>
					<?= isset($errors['style_content']) ? "<div class=\"clear error\">{$this->escape($errors['style_content'])}</div>" : '' ?>
				</div>
			</div>
		</fieldset>
<? if ($mode === 'edit'): ?>
		<p class="status">Last updated by <?= $style->editor_name ?> at <?= $style->edited('h:i A T') ?> on <?= $style->edited('F d, Y') ?></p>
<? endif; ?>
	</div>
	<div class="buttons">
<? if ($showButtons = ($can_save || ($mode === 'edit' && $can_delete))): ?>
<? if ($can_save): ?>
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save <?= ($mode === 'add') ? 'Style' : 'Changes' ?> 
		</button>
<? endif; ?>
<? if ($mode === 'edit' && $can_delete): ?>
		<a class="negative" href="<?= $this->urlTo('/design/styles/delete/'.$selected_style_id) ?>">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Style &ldquo;<?= $styleName ?>&rdquo;
		</a>
<? endif; ?>
<? endif; ?>
	</div>
<? if ($mode === 'edit' && $can_add): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/design/styles/add') ?>">Add New Style</a>
<? elseif ($mode === 'add' && $can_edit): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/design/styles/edit') ?>">Edit Existing Style</a>
<? endif; ?>
</form>
<? elseif ($can_add): ?>
<div class="buttons">
	<a class="positive" href="<?= $this->urlTo('/design/styles/add') ?>">Add New Style</a>
</div>
<? endif; ?>
<div class="clear"></div>
