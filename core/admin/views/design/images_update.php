<? $this->render('form_top'); ?>

<div class="title">
	<?= $mode === 'edit' ? 'Edit' : 'Add' ?> Image
</div>

<? if ($mode === 'edit'): ?>
<form method="post" action="">
<? if (!empty($images) || isset($selected_theme_id)): ?>
<div class="form-area">
<? if (!empty($images)): ?>
	Editing image <select name="selected_image_id">
	<? foreach($images as $id=>$name): ?>
		<option value="<?= $id ?>" <?= ($id == $selected_image_id) ? 'selected="selected"' : '' ?>><?= $this->escape($name) ?></option>
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

<? if (isset($image)): ?>
<? $imageName = (!empty($selected_theme_id) ? ($this->escape(trim($themes[$selected_theme_id])).':') : '') . $this->escape($image->slug); ?>
<form method="post" action="" enctype="multipart/form-data">
	<input type="hidden" name="selected_theme_id" value="<?= @$selected_theme_id ?>" />
	<input type="hidden" name="selected_image_id" value="<?= @$selected_image_id ?>" />
	<div class="form-area">
		<fieldset>
<? if ($mode === 'add'): ?>
			<div class="field collapse">
				<label<?= isset($errors['image_name']) ? ' class="error"' : '' ?> for="image_name">Adding image</label>
				<input type="text" id="image_name" name="image_name" value="<?= $this->escape($image->slug) ?>" />
				<? if (isset($selected_theme_id)): ?>
					for theme <? $this->render('design/theme_selector'); ?>
				<? endif; ?>
				<?= isset($errors['image_name']) ? "<div class=\"error\">{$this->escape($errors['image_name'])}</div>" : '' ?>
			</div>
<? endif; ?>

<? $this->render('metadata_builder', array('prefix'=>'image', 'id'=>$image->id, 'titles'=>array('content_type'=>'Content-Type', 'url'=>'Override URL', 'alt'=>'Alt Text'), 'metadata'=>array('content_type'=>$image->ctype,'url'=>$image->url,'title'=>$image->title,'width'=>$image->width,'height'=>$image->height,'alt'=>$image->alt,'title'=>$image->title))); ?>

<? if ($can_upload || ($mode === 'edit')): ?>
			<div class="field collapse">
				<span class="title"><a href="">Image</a></span>
				<div id="meta_img" class="meta collapsible">
					<fieldset>
<? if ($can_upload): ?>
						<p>
							<label<?= isset($errors['image_upload']) ? ' class="error"' : '' ?> for="image_upload"><?= ($mode === 'add') ? 'Upload' : 'Replace' ?> image</label>
							<input type="hidden" name="MAX_FILE_SIZE" value="<?= $max_upload_size ?>" />
							<input class="textbox" id="image_upload" name="image_upload" type="file" />
							<?= isset($errors['image_upload']) ? "<div class=\"clear error\">{$this->escape($errors['image_upload'])}</div>" : '' ?>
						</p>
<? endif; ?>
<? if ($mode === 'edit'): ?>
						<p>
							<div class="image-display" style="<?= $image->height === '' ? '' : "height:{$image->height}px;" ?><?= $image->width === '' ? '' : "width:{$image->width}px;" ?>">
								<img src="<?= $image->display_url ?>"<?= $image->height === '' ? '' : " height=\"{$image->height}\"" ?><?= $image->width === '' ? '' : " width=\"{$image->width}\"" ?> alt="<?= $this->escape($image->alt) ?>" />
							</div>
						</p>
<? endif; ?>
					</fieldset>
				</div>
			</div>
<? endif; ?>
		</fieldset>
<? if ($mode === 'edit'): ?>
		<p class="status">Last updated by <?= $image->editor_name ?> at <?= $image->edited('h:i A T') ?> on <?= $image->edited('F d, Y') ?></p>
<? endif; ?>
	</div>
	<div class="buttons">
<? if ($showButtons = ($can_save || ($mode === 'edit' && $can_delete))): ?>
<? if ($can_save): ?>
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save <?= ($mode === 'add') ? 'Image' : 'Changes' ?> 
		</button>
<? endif; ?>
<? if ($mode === 'edit' && $can_delete): ?>
		<a class="negative" href="<?= $this->urlTo('/design/images/delete/'.$selected_image_id) ?>">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Image &ldquo;<?= $imageName ?>&rdquo;
		</a>
<? endif; ?>
<? endif; ?>
	</div>
<? if ($mode === 'edit' && $can_add): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/design/images/add') ?>">Add New Image</a>
<? elseif ($mode === 'add' && $can_edit): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/design/images/edit') ?>">Edit Existing Image</a>
<? endif; ?>
</form>
<? elseif ($can_add): ?>
<div class="buttons">
	<a class="positive" href="<?= $this->urlTo('/design/images/add') ?>">Add New Image</a>
</div>
<? endif; ?>
<div class="clear"></div>
