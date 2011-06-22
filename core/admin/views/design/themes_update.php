<? $this->render('form_top'); ?>

<div class="title">
	<?= $mode === 'edit' ? 'Edit' : 'Add' ?> Theme
</div>

<? if (isset($theme)): ?>
<? $themeName = $this->escape($theme->title); ?>
<form method="post" action="">
	<input type="hidden" name="parent_id" value="<?= $theme->parent_id ?>" />
	<input type="hidden" name="theme_id" value="<?= @$theme_id ?>" />
	<div class="form-area">

		<div class="field collapse">
			<label class="title<?= isset($errors['theme_title']) ? ' error' : '' ?>" for="theme_title"><a href="">Title</a></label>
			<div class="collapsible">
				<fieldset>
					<input class="textbox" id="theme_title" maxlength="255" name="theme_title" size="255" type="text" value="<?= $this->escape($theme->title) ?>" />
					<?= isset($errors['theme_title']) ? "<div class=\"error\">{$this->escape($errors['theme_title'])}</div>" : '' ?>
				</fieldset>
			</div>
		</div>

<? if ($mode === 'edit'): ?>
<? $this->render('metadata_builder', array('metadata'=>array('theme'=>array('family'=>$theme->family, 'slug'=>$theme->slug)), 'disabled'=>array('family'))); ?>
<? else: ?>
<? $this->render('metadata_builder', array('metadata'=>array('theme'=>array('family'=>$theme->family, 'slug'=>$theme->slug)))); ?>
<? endif; ?>
<? $this->render('metadata_builder', array('meta_id'=>'ourls', 'title'=>'Override URLs', 'collapsed'=>false, 'titles'=>array('style_url'=>'Style URL','script_url'=>'Script URL','image_url'=>'Image URL'), 'metadata'=>array('theme'=>array('style_url'=>$theme->style_url,'script_url'=>$theme->script_url,'image_url'=>$theme->image_url)))); ?>

		<? if ($mode === 'edit'): ?>
			<p class="status">Created by <?= $theme->author_name ?> at <?= $theme->created('h:i A T') ?> on <?= $theme->created('F d, Y') ?></p>
		<? endif; ?>
	</div>
	<div class="buttons">
<? if ($showButtons = ($can_save || ($mode === 'edit' && $can_delete))): ?>
<? if ($can_save): ?>
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save <?= ($mode === 'add') ? 'Theme' : 'Changes' ?> 
		</button>
<? endif; ?>
<? if ($mode === 'edit' && $can_delete): ?>
		<a class="negative" href="<?= $this->urlTo('/design/themes/delete/'.$theme_id) ?>">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Theme &ldquo;<?= $themeName ?>&rdquo;
		</a>
<? endif; ?>
<? endif; ?>
	</div>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/design/themes') ?>">Cancel</a>
</form>
<? elseif ($can_add): ?>
<div class="buttons">
	<a class="positive" href="<?= $this->urlTo('/design/themes/add') ?>">Add New Theme</a>
</div>
<? endif; ?>
<div class="clear"></div>
