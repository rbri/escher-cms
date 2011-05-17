<? $this->render('form_top'); ?>

<div class="title">
	<?= $mode === 'edit' ? 'Edit' : 'Add' ?> Snippet
</div>

<? if ($mode === 'edit'): ?>
<form method="post" action="">
<? if (!empty($snippets) || isset($selected_theme_id)): ?>
<div class="form-area">
<? if (!empty($snippets)): ?>
	Editing snippet <select name="selected_snippet_id">
	<? foreach($snippets as $id=>$name): ?>
		<option value="<?= $id ?>" <?= ($id == $selected_snippet_id) ? 'selected="selected"' : '' ?>><?= $this->escape($name) ?></option>
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
	in branch
	<? $this->render('design/branch_selector'); ?>
</div>
<? endif; ?>
</form>
<? endif; ?>

<? if (isset($snippet)): ?>
<? $snippetName = (!empty($selected_theme_id) ? ($this->escape(trim($themes[$selected_theme_id])).':') : '') . $this->escape($snippet->name); ?>
<form method="post" action="">
	<input type="hidden" name="selected_theme_id" value="<?= @$selected_theme_id ?>" />
	<input type="hidden" name="selected_snippet_id" value="<?= @$selected_snippet_id ?>" />
	<div class="form-area">
		<fieldset>
<? if ($mode === 'add'): ?>
			<div class="field">
				<label<?= isset($errors['snippet_name']) ? ' class="error"' : '' ?> for="snippet_name">Adding snippet</label>
				<input type="text" id="snippet_name" name="snippet_name" value="<?= $this->escape($snippet->name) ?>" />
				<? if (isset($selected_theme_id)): ?>
					for theme <? $this->render('design/theme_selector'); ?>
				<? endif; ?>
				in branch
				<? $this->render('design/branch_selector'); ?>
				<?= isset($errors['snippet_name']) ? "<div class=\"error\">{$this->escape($errors['snippet_name'])}</div>" : '' ?>
			</div>
<? endif; ?>
			<textarea id="snippet_content" class="code" name="snippet_content" rows="3" cols="80"><?= $this->escape($snippet->content) ?></textarea>
			<?= isset($errors['snippet_content']) ? "<div class=\"error\">{$this->escape($errors['snippet_content'])}</div>" : '' ?>
		</fieldset>
<? if ($mode === 'edit'): ?>
		<p class="status">Last updated by <?= $snippet->editor_name ?> at <?= $snippet->edited('h:i A T') ?> on <?= $snippet->edited('F d, Y') ?></p>
<? endif; ?>
	</div>
	<div class="buttons">
<? if ($showButtons = ($can_save || ($mode === 'edit' && $can_delete))): ?>
<? if ($can_save): ?>
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save <?= ($mode === 'add') ? 'Snippet' : 'Changes' ?> 
		</button>
<? endif; ?>
<? if ($mode === 'edit' && $can_delete): ?>
		<a class="negative" href="<?= $this->urlTo('/design/snippets/delete/'.$selected_snippet_id) ?>">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Snippet &ldquo;<?= $snippetName ?>&rdquo;
		</a>
<? endif; ?>
<? endif; ?>
	</div>
<? if ($mode === 'edit' && $can_add): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/design/snippets/add') ?>">Add New Snippet</a>
<? elseif ($mode === 'add' && $can_edit): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/design/snippets/edit') ?>">Edit Existing Snippet</a>
<? endif; ?>
</form>
<? elseif ($can_add): ?>
<div class="buttons">
	<a class="positive" href="<?= $this->urlTo('/design/snippets/add') ?>">Add New Snippet</a>
</div>
<? endif; ?>
<div class="clear"></div>
