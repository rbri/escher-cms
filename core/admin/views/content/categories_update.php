<? $this->render('form_top'); ?>

<div class="title">
	<?= $mode === 'edit' ? 'Edit' : 'Add' ?> Category
</div>

<? if (isset($category)): ?>
<form method="post" action="">
	<input type="hidden" name="parent_id" value="<?= $category->parent_id ?>" />
	<input type="hidden" name="category_id" value="<?= @$category_id ?>" />
	<div class="form-area">

		<div class="field collapse">
			<label class="title<?= isset($errors['category_title']) ? ' error' : '' ?>" for="category_title"><a href="">Title</a></label>
			<div class="collapsible">
				<fieldset>
					<input class="textbox" id="category_title" maxlength="255" name="category_title" size="255" type="text" value="<?= $this->escape($category->title) ?>" />
					<?= isset($errors['category_title']) ? "<div class=\"error\">{$this->escape($errors['category_title'])}</div>" : '' ?>
				</fieldset>
			</div>
		</div>

<? $this->render('metadata_builder', array('prefix'=>'category', 'metadata'=>array('slug'=>$category->slug))); ?>

	</div>
	<div class="buttons">
<? if ($showButtons = ($can_save || ($mode === 'edit' && $can_delete))): ?>
<? if ($can_save): ?>
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save <?= ($mode === 'add') ? 'Category' : 'Changes' ?> 
		</button>
<? endif; ?>
<? if ($mode === 'edit' && $can_delete): ?>
		<a class="negative" href="<?= $this->urlTo('/content/categories/delete/'.$category_id) ?>">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Category
		</a>
<? endif; ?>
<? endif; ?>
	</div>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/content/categories') ?>">Cancel</a>
</form>
<? elseif ($can_add): ?>
<div class="buttons">
	<a class="positive" href="<?= $this->urlTo('/content/categories/add') ?>">Add New Category</a>
</div>
<? endif; ?>
<div class="clear"></div>
