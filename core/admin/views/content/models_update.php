<? $this->render('form_top'); ?>

<div class="title">
	<?= $mode === 'edit' ? 'Edit' : 'Add' ?> Model
</div>

<? if (isset($model)): ?>
<form method="post" action="">
	<input type="hidden" name="model_id" value="<?= @$model_id ?>" />
	<div class="form-area">

		<div class="field collapse">
			<label class="title<?= isset($errors['model_name']) ? ' error' : '' ?>" for="model_name"><a href="">Name</a></label>
			<div class="collapsible">
				<fieldset>
					<input class="textbox" id="model_name" maxlength="255" name="model_name" size="255" type="text" value="<?= $this->escape($model->name) ?>" />
					<?= isset($errors['model_name']) ? "<div class=\"error\">{$this->escape($errors['model_name'])}</div>" : '' ?>
				</fieldset>
			</div>
		</div>

<? $this->render('metadata_builder', array('collapsed'=>($mode === 'edit'), 'toolbar'=>true,'prefix'=>'meta', 'metadata'=>$model->meta)); ?>
<? $this->render('category_builder', array('categories'=>$model->categories)); ?>
<? $this->render('part_builder', array('parts'=>$model->parts)); ?>
		
		<div id="options">
			<fieldset>
				<p>
					<label class="title<?= isset($errors['model_template']) ? ' error' : '' ?>" for="model_template">Template</label>
					<select <?= !$can_edit_template ? 'disabled="disabled "' : '' ?>id="model_template" name="model_template_name">
						<option value="">&lt;inherit&gt;</option>
<? foreach($templates as $id => $name): ?>
						<option value="<?= $this->escape($name) ?>"<?= ($name == $model->template_name) ? ' selected="selected"' : '' ?>><?= $this->escape($name) ?></option>
<? endforeach; ?>
					</select>
				</p>
				<p>
					<label class="title<?= isset($errors['model_type']) ? ' error' : '' ?>" for="model_type">Page Type</label>
					<select <?= !$can_edit_pagetype ? 'disabled="disabled "' : '' ?>id="model_type" name="model_type">
<? foreach($model_types as $id => $modelType): ?>
						<option value="<?= $id ?>"<?= ($id == $model->type) ? ' selected="selected"' : '' ?>><?= $this->escape($modelType) ?></option>
<? endforeach; ?>
					</select>
				</p>
				<p>
					<label class="title<?= isset($errors['model_status']) ? ' error' : '' ?>" for="model_status">Status</label>
					<select <?= !$can_edit_status ? 'disabled="disabled "' : '' ?>id="model_status" name="model_status">
<? foreach($statuses as $id => $status): ?>
						<option value="<?= $id ?>"<?= ($id == $model->status) ? ' selected="selected"' : '' ?>><?= $this->escape($status) ?></option>
<? endforeach; ?>
					</select>
				</p>
				<p>
					<label class="title<?= isset($errors['model_magic']) ? ' error' : '' ?>" for="model_magic">Magic</label>
					<select <?= !$can_edit_magic ? 'disabled="disabled "' : '' ?>id="model_magic" name="model_magic">
						<option value="0"<?= (!$model->magical) ? ' selected="selected"' : '' ?>>No</option>
						<option value="1"<?= ($model->magical) ? ' selected="selected"' : '' ?>>Yes</option>
					</select>
				</p>
				<p>
					<label class="title<?= isset($errors['model_cacheable']) ? ' error' : '' ?>" for="model_cacheable">Cacheable</label>
					<select <?= !$can_edit_cacheable ? 'disabled="disabled "' : '' ?>id="model_cacheable" name="model_cacheable">
						<option value="-1"<?= ($model->cacheable == -1) ? ' selected="selected"' : '' ?>>&lt;inherit&gt;</option>
						<option value="0"<?= ($model->cacheable == 0) ? ' selected="selected"' : '' ?>>No</option>
						<option value="1"<?= ($model->cacheable == 1) ? ' selected="selected"' : '' ?>>Yes</option>
					</select>
				</p>
				<p>
					<label class="title<?= isset($errors['model_secure']) ? ' error' : '' ?>" for="model_secure">Secure</label>
					<select <?= !$can_edit_secure ? 'disabled="disabled "' : '' ?>id="model_secure" name="model_secure">
						<option value="-1"<?= ($model->secure == -1) ? ' selected="selected"' : '' ?>>&lt;inherit&gt;</option>
						<option value="0"<?= ($model->secure == 0) ? ' selected="selected"' : '' ?>>No</option>
						<option value="1"<?= ($model->secure == 1) ? ' selected="selected"' : '' ?>>Yes</option>
					</select>
				</p>
			</fieldset>
		</div>
		
		<? if ($mode === 'edit'): ?>
			<p class="status">Last updated by <?= $model->editor_name ?> at <?= $model->edited('h:i A T') ?> on <?= $model->edited('F d, Y') ?></p>
		<? endif; ?>
	</div>
	<div class="buttons">
<? if ($showButtons = ($can_save || ($mode === 'edit' && $can_delete))): ?>
<? if ($can_save): ?>
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save <?= ($mode === 'add') ? 'Model' : 'Changes' ?> 
		</button>
		<button class="positive" type="submit" name="continue">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save and Continue Editing
		</button>
<? endif; ?>
<? if ($mode === 'edit' && $can_delete): ?>
		<a class="negative" href="<?= $this->urlTo('/content/models/delete/'.$model_id) ?>">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Model
		</a>
<? endif; ?>
<? endif; ?>
	</div>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/content/models') ?>">Cancel</a>
</form>
<? elseif ($can_add): ?>
<div class="buttons">
	<a class="positive" href="<?= $this->urlTo('/content/models/add') ?>">Add New Model</a>
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
