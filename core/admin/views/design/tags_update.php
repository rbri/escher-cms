<? $this->render('form_top'); ?>

<div class="title">
	<?= $mode === 'edit' ? 'Edit' : 'Add' ?> Tag
</div>

<? if ($mode === 'edit'): ?>
<form method="post" action="">
<? if (!empty($tags) || isset($selected_theme_id)): ?>
<div class="form-area">
<? if (!empty($tags)): ?>
	Editing tag <select name="selected_tag_id">
	<? foreach($tags as $id=>$name): ?>
		<option value="<?= $id ?>"<?= ($id == $selected_tag_id) ? ' selected="selected"' : '' ?>><?= $this->escape($name) ?></option>
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

<? if (isset($tag)): ?>
<? $tagName = (!empty($selected_theme_id) ? ($this->escape(trim($themes[$selected_theme_id])).':') : '') . $this->escape($tag->name); ?>
<form method="post" action="">
	<input type="hidden" name="selected_theme_id" value="<?= @$selected_theme_id ?>" />
	<input type="hidden" name="selected_tag_id" value="<?= @$selected_tag_id ?>" />
	<div class="form-area">
		<fieldset>
<? if ($mode === 'add'): ?>
			<div class="field">
				<label<?= isset($errors['tag_name']) ? ' class="error"' : '' ?> for="tag_name">Adding tag</label>
				<input type="text" id="tag_name" name="tag_name" value="<?= $this->escape($tag->name) ?>" />
				<? if (isset($selected_theme_id)): ?>
					for theme <? $this->render('design/theme_selector'); ?>
				<? endif; ?>
				in branch
				<? $this->render('design/branch_selector'); ?>
				<?= isset($errors['tag_name']) ? "<div class=\"error\">{$this->escape($errors['tag_name'])}</div>" : '' ?>
			</div>
<? endif; ?>
			<textarea id="tag_content" class="code" name="tag_content" rows="3" cols="80"><?= $this->escape($tag->content) ?></textarea>
			<?= isset($errors['tag_content']) ? "<div class=\"error\">{$this->escape($errors['tag_content'])}</div>" : '' ?>
		</fieldset>
<? if ($mode === 'edit'): ?>
		<p class="status">Last updated by <?= $tag->editor_name ?> at <?= $tag->edited('h:i A T') ?> on <?= $tag->edited('F d, Y') ?></p>
<? endif; ?>
	</div>
	<div class="buttons">
<? if ($showButtons = ($can_save || ($mode === 'edit' && $can_delete))): ?>
<? if ($can_save): ?>
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save <?= ($mode === 'add') ? 'Tag' : 'Changes' ?>
		</button>
<? endif; ?>
<? if ($mode === 'edit' && $can_delete): ?>
		<a class="negative" href="<?= $this->urlTo('/design/tags/delete/'.$selected_tag_id) ?>">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Tag &ldquo;<?= $tagName ?>&rdquo;
		</a>
<? endif; ?>
<? endif; ?>
	</div>
<? if ($mode === 'edit' && $can_add): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/design/tags/add') ?>">Add New Tag</a>
<? elseif ($mode === 'add' && $can_edit): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/design/tags/edit') ?>">Edit Existing Tag</a>
<? endif; ?>
</form>
<? elseif ($can_add): ?>
<div class="buttons">
	<a class="positive" href="<?= $this->urlTo('/design/tags/add') ?>">Add New Tag</a>
</div>
<? endif; ?>
<div class="clear"></div>
