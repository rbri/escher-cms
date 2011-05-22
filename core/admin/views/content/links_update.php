<? $this->render('form_top'); ?>

<div class="title">
	<?= $mode === 'edit' ? 'Edit' : 'Add' ?> Link
</div>

<? if ($mode === 'edit'): ?>
<form method="post" action="">
<? if (!empty($links)): ?>
<div class="form-area">
	Editing link <select name="selected_link_id">
	<? foreach($links as $id=>$name): ?>
		<option value="<?= $id ?>"<?= ($id == $selected_link_id) ? ' selected="selected"' : '' ?>><?= $this->escape($name) ?></option>
	<? endforeach; ?>
	</select>
	<input type="submit" name="go" value="Switch" />
</div>
<? endif; ?>
</form>
<? endif; ?>

<? if (isset($link) || ($mode === 'add')): ?>
<? $linkName = $this->escape($link->name); ?>
<form method="post" action="">
	<input type="hidden" name="selected_link_id" value="<?= @$selected_link_id ?>" />
	<fieldset>
		<div class="form-area">
<? if ($mode === 'add'): ?>
			<div class="field">
				<label<?= isset($errors['link_name']) ? ' class="error"' : '' ?> for="link_name">Adding link</label>
				<input type="text" id="link_name" name="link_name" value="<?= $this->escape($link->name) ?>" />
				<?= isset($errors['link_name']) ? "<div class=\"error\">{$this->escape($errors['link_name'])}</div>" : '' ?>
			</div>
<? endif; ?>
			<div class="field collapse">
				<label class="title<?= isset($errors['link_url']) ? ' error' : '' ?>" for="link_url"><a href="">URL</a></label>
				<div class="collapsible">
					<input class="textbox" id="link_url" maxlength="255" name="link_url" size="255" type="text" value="<?= $this->escape($link->url) ?>" />
					<?= isset($errors['link_url']) ? "<div class=\"error\">{$this->escape($errors['link_url'])}</div>" : '' ?>
				</div>
			</div>
<? $this->render('metadata_builder', array('id'=>$link->id, 'toolbar'=>true, 'metadata'=>array('link'=>$fixed_meta=array('title'=>$link->title,'description'=>$link->description), 'meta'=>$link->meta), 'protected'=>array_keys($fixed_meta))); ?>
<? $this->render('category_builder', array('categories'=>$link->categories)); ?>
		<? if ($mode === 'edit'): ?>
			<p class="status">Last updated by <?= $link->editor_name ?> at <?= $link->edited('h:i A T') ?> on <?= $link->edited('F d, Y') ?></p>
		<? endif; ?>
		</div>
	</fieldset>
	<div class="buttons">
<? if ($showButtons = ($can_save || ($mode === 'edit' && $can_delete))): ?>
<? if ($can_save): ?>
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save <?= ($mode === 'add') ? 'Link' : 'Changes' ?> 
		</button>
<? endif; ?>
<? if ($mode === 'edit' && $can_delete): ?>
		<a class="negative" href="<?= $this->urlTo('/content/links/delete/'.$selected_link_id) ?>">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Link &ldquo;<?= $linkName ?>&rdquo;
		</a>
<? endif; ?>
<? endif; ?>
	</div>
<? if ($mode === 'edit' && $can_add): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/content/links/add') ?>">Add New Link</a>
<? elseif ($mode === 'add' && $can_edit): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/content/links/edit') ?>">Edit Existing Link</a>
<? endif; ?>
</form>
<? elseif ($can_add): ?>
<div class="buttons">
	<a class="positive" href="<?= $this->urlTo('/content/links/add') ?>">Add New Link</a>
</div>
<? endif; ?>
<div class="clear"></div>

<div id="popups">
	<div class="popup" id="add-meta-popup">
		<h3>Add Meta Data</h3>
		<div>
			<form id="add-meta-form" action="">
				<label for="meta_name">Meta Type: </label><input id="meta_name_input" name="meta_name_input" size="30" type="text" />
				<input id="add-meta-button" type="submit" value="Add Meta" />
			</form>
		</div>
		<div class="close-link">
			<a class="meta_close_link" href="">Close</a>
		</div>
	</div>
</div>
