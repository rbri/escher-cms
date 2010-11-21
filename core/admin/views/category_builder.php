<div class="field">
	<span class="title"><a class="collapse" href="">Categories</a></span>
	<div id="meta_cat" class="meta collapsible persistent">
		<fieldset>
			<p>
				<label<?= isset($errors['add_categories']) ? ' class="error"' : '' ?>>Categories</label>
				<span id="categories" class="textbox categories">
<? foreach($categories as $category): ?>
					<span><?= $can_delete_categories ? '<a class="delete_category_link" href="">' : '' ?><?= $this->escape($category->title) ?><?= $can_delete_categories ? '</a>' : '' ?><input type="hidden" name="add_categories[]" value="<?= $category->id ?>" /></span>
<? endforeach; ?>
				</span>
				<?= isset($errors['add_categories']) ? "<div class=\"clear error\">{$this->escape($errors['add_categories'])}</div>" : '' ?>
			</p>
		</fieldset>
		<div class="toolbar" style="padding:5px 0 5px 0;">
<? if ($can_add_categories): ?>
		<select name="add_category" id="add_category">
			<option value="0">Add Category</option>
<? foreach($category_titles as $id => $title): ?>
			<option value="<?= $id ?>"><?= str_replace('  ', '&nbsp;&nbsp;', $this->escape($title)) ?></option>
<? endforeach; ?>
		</select>
<? endif; ?>
		</div>
	</div>
</div>
