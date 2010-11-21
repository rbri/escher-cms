<? $this->render('form_top'); ?>

<div class="title">
	<?= $mode === 'edit' ? 'Edit' : 'Add' ?> Block
</div>

<? if ($mode === 'edit'): ?>
<form method="post" action="">
<? if (!empty($blocks)): ?>
<div class="form-area">
	Editing block <select name="selected_block_id">
	<? foreach($blocks as $id=>$name): ?>
		<option value="<?= $id ?>" <?= ($id == $selected_block_id) ? 'selected="selected"' : '' ?>><?= $this->escape($name) ?></option>
	<? endforeach; ?>
	</select>
	<input type="submit" name="go" value="Switch" />
</div>
<? endif; ?>
</form>
<? endif; ?>

<? if (isset($block) || ($mode === 'add')): ?>
<? $blockName = $this->escape($block->name); ?>
<form method="post" action="">
	<input type="hidden" name="selected_block_id" value="<?= @$selected_block_id ?>" />
	<fieldset>
		<div class="form-area">
<? if ($mode === 'add'): ?>
			<div class="field">
				<label<?= isset($errors['block_name']) ? ' class="error"' : '' ?> for="block_name">Adding block</label>
				<input type="text" id="block_name" name="block_name" value="<?= $this->escape($block->name) ?>" />
				<?= isset($errors['block_name']) ? "<div class=\"error\">{$this->escape($errors['block_name'])}</div>" : '' ?>
			</div>
<? endif; ?>
			<div class="field collapse">
				<label class="title<?= isset($errors['block_title']) ? ' error' : '' ?>" for="block_title"><a href="">Title</a></label>
				<div class="collapsible">
					<input class="textbox" id="block_title" maxlength="255" name="block_title" size="255" type="text" value="<?= $this->escape($block->title) ?>" />
					<?= isset($errors['block_title']) ? "<div class=\"error\">{$this->escape($errors['block_title'])}</div>" : '' ?>
				</div>
			</div>

<? $this->render('category_builder', array('categories'=>$block->categories)); ?>

			<div class="field collapse">
				<label class="title<?= isset($errors['block_content']) ? ' error' : '' ?>" for="block_content"><a href="">Body</a></label>
				<div class="collapsible">
<? $filterClass = ''; ?>
<? if (!empty($filterNames)): ?>
					<div class="filter">
						<label for="block_content_filter">Filter:</label>
						<select name="block_content_filter" id="block_content_filter">
<? foreach($filterNames as $filterID => $filterName): ?>
	<? if ($filterID == $block->filter_id): ?>
		<? $filterClass = empty($filterClasses[$filterID]) ? '' : ' '.$filterClasses[$filterID]; ?>
							<option value="<?= $filterID ?>" selected="selected"><?= $filterName ?></option>
	<? else: ?>
							<option value="<?= $filterID ?>"><?= $filterName ?></option>
	<? endif; ?>
<? endforeach; ?>
						</select>
					</div>
<? endif; ?>
					<textarea id="block_content" class="code<?= $filterClass ?>" name="block_content" rows="3" cols="80"><?= $this->escape($block->content) ?></textarea>
					<?= isset($errors['block_content']) ? "<div class=\"error\">{$this->escape($errors['block_content'])}</div>" : '' ?>
				</div>
			</div>
			<? if ($mode === 'edit'): ?>
				<p class="status">Last updated by <?= $block->editor_name ?> at <?= $block->edited('h:i A T') ?> on <?= $block->edited('F d, Y') ?></p>
			<? endif; ?>
		</div>
	</fieldset>
	<div class="buttons">
<? if ($showButtons = ($can_save || ($mode === 'edit' && $can_delete))): ?>
<? if ($can_save): ?>
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save <?= ($mode === 'add') ? 'Block' : 'Changes' ?> 
		</button>
<? endif; ?>
<? if ($mode === 'edit' && $can_delete): ?>
		<a class="negative" href="<?= $this->urlTo('/content/blocks/delete/'.$selected_block_id) ?>">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Block &ldquo;<?= $blockName ?>&rdquo;
		</a>
<? endif; ?>
<? endif; ?>
	</div>
<? if ($mode === 'edit' && $can_add): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/content/blocks/add') ?>">Add New Block</a>
<? elseif ($mode === 'add' && $can_edit): ?>
	<?= $showButtons ? 'or' : '' ?> <a href="<?= $this->urlTo('/content/blocks/edit') ?>">Edit Existing Block</a>
<? endif; ?>
</form>
<? elseif ($can_add): ?>
<div class="buttons">
	<a class="positive" href="<?= $this->urlTo('/content/blocks/add') ?>">Add New Block</a>
</div>
<? endif; ?>
<div class="clear"></div>
