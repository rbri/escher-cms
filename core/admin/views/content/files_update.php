<? $this->render('form_top'); ?>

<div class="title">
	<?= $mode === 'edit' ? 'Edit' : 'Add' ?> File
</div>

<? if ($mode === 'edit'): ?>
<form method="post" action="">
<? if (!empty($files)): ?>
<div class="form-area">
	Editing file <select name="selected_file_id">
	<? foreach($files as $id=>$name): ?>
		<option value="<?= $id ?>" <?= ($id == $selected_file_id) ? 'selected="selected"' : '' ?>><?= $this->escape($name) ?></option>
	<? endforeach; ?>
	</select>
	<input type="submit" name="go" value="Switch" />
</div>
<? endif; ?>
</form>
<? endif; ?>

<? if (isset($file)): ?>
<form method="post" action="" enctype="multipart/form-data">
	<div class="form-area">
		<input type="hidden" name="selected_file_id" value="<?= @$selected_file_id ?>" />
		<fieldset>
<? if ($mode === 'add'): ?>
			<div class="field collapse">
				<label<?= isset($errors['file_name']) ? ' class="error"' : '' ?> for="file_name">Adding file</label>
				<input type="text" id="file_name" name="file_name" value="<?= $this->escape($file->slug) ?>" />
				<?= isset($errors['file_name']) ? "<div class=\"error\">{$this->escape($errors['file_name'])}</div>" : '' ?>
			</div>
<? endif; ?>

<? $this->render('metadata_builder', array('id'=>$file->id, 'toolbar'=>true, 'titles'=>array('content_type'=>'Content-Type', 'url'=>'Override URL'), 'metadata'=>array('file'=>$fixed_meta=array('content_type'=>$file->ctype,'url'=>$file->url,'title'=>$file->title,'description'=>$file->description), 'meta'=>$file->meta), 'protected'=>array_keys($fixed_meta))); ?>
<? $this->render('category_builder', array('categories'=>$file->categories)); ?>

<? if ($can_upload): ?>
			<div class="field collapse">
				<span class="title"><a href="">File</a></span>
				<div id="meta_img" class="meta collapsible">
					<fieldset>
						<p>
							<label<?= isset($errors['file_upload']) ? ' class="error"' : '' ?> for="file_upload"><?= ($mode === 'add') ? 'Upload' : 'Replace' ?> file</label>
							<input type="hidden" name="MAX_FILE_SIZE" value="<?= $max_upload_size ?>" />
							<input class="textbox" id="file_upload" name="file_upload" type="file" />
							<?= isset($errors['file_upload']) ? "<div class=\"clear error\">{$this->escape($errors['file_upload'])}</div>" : '' ?>
						</p>
					</fieldset>
				</div>
			</div>
<? endif; ?>

		<div id="options">
			<fieldset>
				<p>
					<label class="title<?= isset($errors['file_status']) ? ' error' : '' ?>" for="file_status">Status</label>
					<select id="file_status" name="file_status">
<? foreach($statuses as $id=>$status): ?>
						<option value="<?= $id ?>"<?= ($id == $file->status) ? ' selected="selected"' : '' ?>><?= $this->escape($status) ?></option>
<? endforeach; ?>
					</select>
				</p>
				<p>
					<label class="title<?= isset($errors['file_download']) ? ' error' : '' ?>" for="file_download">Force Download</label>
					<select id="file_download" name="file_download">
						<option value="0"<?= !$file->download ? ' selected="selected"' : '' ?>>No</option>
						<option value="1"<?= $file->download ? ' selected="selected"' : '' ?>>Yes</option>
					</select>
				</p>
			</fieldset>
		</div>

<? if ($mode === 'edit'): ?>
			<p class="status">Last updated by <?= $file->editor_name ?> at <?= $file->edited('h:i A T') ?> on <?= $file->edited('F d, Y') ?></p>
<? endif; ?>
		</fieldset>
	</div>
	<div class="buttons">
<? if ($showButtons = ($can_save || ($mode === 'edit' && $can_delete))): ?>
<? if ($can_save): ?>
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save <?= ($mode === 'add') ? 'File' : 'Changes' ?>
		</button>
<? endif; ?>
<? if ($mode === 'edit' && $can_delete): ?>
		<a class="negative" href="<?= $this->urlTo('/content/files/delete/'.$selected_file_id) ?>">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete File &ldquo;<?= $this->escape($file->slug) ?>&rdquo;
		</a>
<? endif; ?>
<? endif; ?>
	</div>
<? if ($mode === 'edit' && $can_add): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/content/files/add') ?>">Add New File</a>
<? elseif ($mode === 'add' && $can_edit): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/content/files/edit') ?>">Edit Existing File</a>
<? endif; ?>
</form>
<? elseif ($can_add): ?>
<div class="buttons">
	<a class="positive" href="<?= $this->urlTo('/content/files/add') ?>">Add New File</a>
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
