<? $this->render('form_top'); ?>

<div class="title">
<? if ($mode === 'edit'): ?>
	Edit Page: <?= $page->uri() ?>
<? else: ?>
	Add Page
<? endif; ?>
</div>

<? if (isset($page)): ?>
<form method="post" action="">
	<input type="hidden" name="parent_id" value="<?= $page->parent_id ?>" />
	<input type="hidden" name="model_id" value="<?= $page->model_id ?>" />
	<input type="hidden" name="page_id" value="<?= @$page_id ?>" />
	<div class="form-area">

		<div class="field">
			<label class="title<?= isset($errors['page_title']) ? ' error' : '' ?>" for="page_title"><a class="collapse" href="">Title</a></label>
			<div id="ftitle" class="collapsible persistent">
				<fieldset>
					<input class="textbox" id="page_title" maxlength="255" name="page_title" size="255" type="text" value="<?= $this->escape($page->title) ?>" />
					<?= isset($errors['page_title']) ? "<div class=\"error\">{$this->escape($errors['page_title'])}</div>" : '' ?>
				</fieldset>
			</div>
		</div>

<? if ($page->parent_id): ?>
<?	$fixed_meta['slug'] = $page->slug; ?>
<? endif; ?>
<?	$fixed_meta['breadcrumb'] = $page->breadcrumb; ?>

<? $this->render('metadata_builder', array('collapsed'=>($mode === 'edit'), 'toolbar'=>true, 'id'=>$page->id, 'metadata'=>array('page'=>$fixed_meta, 'meta'=>$page->meta), 'protected'=>array_keys($fixed_meta))); ?>
<? $this->render('category_builder', array('categories'=>$page->categories)); ?>
<? $this->render('part_builder', array('parts'=>$page->parts)); ?>
		
		<div id="options">
			<fieldset>
				<p>
					<label class="title<?= isset($errors['page_template_name']) ? ' error' : '' ?>" for="page_template">Template</label>
					<select <?= !$can_edit_template ? 'disabled="disabled "' : '' ?>id="page_template" name="page_template_name">
<? if ($can_inherit): ?>
						<option value="">&lt;inherit&gt;</option>
<? endif; ?>
<? foreach($templates as $id => $name): ?>
						<option value="<?= $this->escape($name) ?>"<?= ($name == $page->template_name) ? ' selected="selected"' : '' ?>><?= $this->escape($name) ?></option>
<? endforeach; ?>
					</select>
				</p>
				<p>
					<label class="title<?= isset($errors['page_type']) ? ' error' : '' ?>" for="page_type">Page Type</label>
					<select <?= !$can_edit_pagetype ? 'disabled="disabled "' : '' ?>id="page_type" name="page_type">
<? foreach($page_types as $id => $pageType): ?>
						<option value="<?= $id ?>"<?= ($id == $page->type) ? ' selected="selected"' : '' ?>><?= $this->escape($pageType) ?></option>
<? endforeach; ?>
					</select>
				</p>
				<p>
					<label class="title<?= isset($errors['page_status']) ? ' error' : '' ?>" for="page_status">Status</label>
					<select <?= !$can_edit_status ? 'disabled="disabled "' : '' ?>id="page_status" name="page_status">
<? foreach($statuses as $id => $status): ?>
						<option value="<?= $id ?>"<?= ($id == $page->status) ? ' selected="selected"' : '' ?>><?= $this->escape($status) ?></option>
<? endforeach; ?>
					</select>
				</p>
				<p>
					<label class="title<?= isset($errors['page_magic']) ? ' error' : '' ?>" for="page_magic">Magic</label>
					<select <?= !$can_edit_magic ? 'disabled="disabled "' : '' ?>id="page_magic" name="page_magic">
						<option value="0"<?= (!$page->magical) ? ' selected="selected"' : '' ?>>No</option>
						<option value="1"<?= ($page->magical) ? ' selected="selected"' : '' ?>>Yes</option>
					</select>
				</p>
				<p>
					<label class="title<?= isset($errors['page_cacheable']) ? ' error' : '' ?>" for="page_cacheable">Cacheable</label>
					<select <?= !$can_edit_cacheable ? 'disabled="disabled "' : '' ?>id="page_cacheable" name="page_cacheable">
<? if ($can_inherit): ?>
						<option value="-1"<?= ($page->cacheable == -1) ? ' selected="selected"' : '' ?>>&lt;inherit&gt;</option>
<? endif; ?>
						<option value="0"<?= ($page->cacheable == 0) ? ' selected="selected"' : '' ?>>No</option>
						<option value="1"<?= ($page->cacheable == 1) ? ' selected="selected"' : '' ?>>Yes</option>
					</select>
				</p>
				<p>
					<label class="title<?= isset($errors['page_secure']) ? ' error' : '' ?>" for="page_secure">Secure</label>
					<select <?= !$can_edit_secure ? 'disabled="disabled "' : '' ?>id="page_secure" name="page_secure">
<? if ($can_inherit): ?>
						<option value="-1"<?= ($page->secure == -1) ? ' selected="selected"' : '' ?>>&lt;inherit&gt;</option>
<? endif; ?>
						<option value="0"<?= ($page->secure == 0) ? ' selected="selected"' : '' ?>>No</option>
						<option value="1"<?= ($page->secure == 1) ? ' selected="selected"' : '' ?>>Yes</option>
					</select>
				</p>
			</fieldset>
		</div>
		<?= isset($errors['page_template_name']) ? "<div class=\"error\">{$this->escape($errors['page_template_name'])}</div>" : '' ?>
		<?= isset($errors['page_type']) ? "<div class=\"error\">{$this->escape($errors['page_type'])}</div>" : '' ?>
		<?= isset($errors['page_status']) ? "<div class=\"error\">{$this->escape($errors['page_status'])}</div>" : '' ?>
		<?= isset($errors['page_magic']) ? "<div class=\"error\">{$this->escape($errors['page_magic'])}</div>" : '' ?>
		<?= isset($errors['page_cacheable']) ? "<div class=\"error\">{$this->escape($errors['page_cacheable'])}</div>" : '' ?>
		<?= isset($errors['page_secure']) ? "<div class=\"error\">{$this->escape($errors['page_secure'])}</div>" : '' ?>
		
		<? if ($mode === 'edit'): ?>
			<p class="status">Last updated by <?= $page->editor_name ?> at <?= $page->edited('h:i A T') ?> on <?= $page->edited('F d, Y') ?></p>
		<? endif; ?>
	</div>
	<div class="buttons">
<? if ($showButtons = ($can_save || ($mode === 'edit' && $can_delete))): ?>
<? if ($can_save): ?>
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save <?= ($mode === 'add') ? 'Page' : 'Changes' ?> 
		</button>
		<button class="positive" type="submit" name="continue">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save and Continue Editing
		</button>
<? endif; ?>
<? if ($mode === 'edit' && $can_delete): ?>
		<a class="negative" href="<?= $this->urlTo('/content/pages/delete/'.$page_id) ?>">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Page
		</a>
<? endif; ?>
<? endif; ?>
	</div>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/content/pages') ?>">Cancel</a>
</form>
<? elseif ($can_add): ?>
<div class="buttons">
	<a class="positive" href="<?= $this->urlTo('/content/pages/add') ?>">Add New Page</a>
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
