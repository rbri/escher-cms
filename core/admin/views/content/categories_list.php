<?php
	function outputCategories($category, $level, $self, $canAdd, $canEdit, $canDelete, $imageBase, $treeState)
	{
		if (!$category)
		{
			return;
		}
		
		$tabs = str_repeat("\t", $level);
		$categoryID = $category->id;
		$categoryTitle = SparkView::escape_html($category->title);
		$categoryClass = empty($category->children) ? ' no-children' : '';
		$childDisplayClass = isset($treeState[$categoryID]) ? ($treeState[$categoryID] ? ' hidden' : '') : (($level > 0) ? ' hidden' : '');

		$out = <<<EOD
		{$tabs}<li id="category_{$categoryID}">
			{$tabs}<div class="entry">
				{$tabs}<div class="column first{$categoryClass}">
					{$tabs}<span>

EOD;
		if (!empty($category->children))
		{
			$expand_collpase = isset($treeState[$categoryID]) ? ($treeState[$categoryID] ? 'expand' : 'collapse') : (($level > 0) ? 'expand' : 'collapse');
			$out .= <<<EOD
						{$tabs}<a class="{$expand_collpase}" href=""></a>

EOD;
		}
		if ($canEdit)
		{
			$out .= <<<EOD
						{$tabs}<a href="{$self->urlTo("/content/categories/edit/{$categoryID}")}" title="{$categoryTitle}"><img alt="category-icon" class="icon" src="{$imageBase}category.png" title="" /><span class="title">{$categoryTitle}</span></a>

EOD;
		}
		else
		{
			$out .= <<<EOD
						{$tabs}<img alt="category-icon" class="icon" src="{$imageBase}category.png" title="" /><span class="title">{$categoryTitle}</span>

EOD;
		}
			$out .= <<<EOD
						{$tabs}<img alt="" class="busy" src="{$imageBase}spinner.gif" style="display: none;" title="" />
					{$tabs}</span>
				{$tabs}</div>
				{$tabs}<div class="column action">

EOD;
if ($canAdd)
{
			$out .= <<<EOD
				{$tabs}<a href="{$self->urlTo("/content/categories/add/{$categoryID}")}"><img alt="add child" title="add child" src="{$imageBase}plus.png" /></a>&nbsp;

EOD;
}
if ($canDelete)
{
			$out .= <<<EOD
				{$tabs}<a href="{$self->urlTo("/content/categories/delete/{$categoryID}")}"><img title="delete category" alt="delete category" src="{$imageBase}minus.png" /></a>

EOD;
}
			$out .= <<<EOD
			{$tabs}</div>
		{$tabs}</div>

EOD;

		if ($children = $category->children)
		{
			++$level;
			
			$out .= <<<EOD
			{$tabs}<ul class="level-{$level} collapsible{$childDisplayClass}">

EOD;
			foreach ($children as $child)
			{
				$out .= outputCategories($child, $level, $self, $canAdd, $canEdit, $canDelete, $imageBase, $treeState);
			}
			$out .= <<<EOD
		{$tabs}</ul>

EOD;
		}

		$out .= <<<EOD
		{$tabs}</li>

EOD;
		return $out;
	}
?>

<? $this->render('alert'); ?>

<div class="title">
	Categories
</div>

<div id="page-header">
	<ul>
		<li class="category">Category</li>
		<li class="action">Action</li>
	</ul>
</div>

<div id="cat-list" class="hier-list persistent">
	<ul class="level-0">
<? if (empty($categories)): ?>
	<li class="no-entries">No Categories</li>
<? else: ?>
	<? foreach ($categories as $category): ?>
		<?= outputCategories($category, 0, $this, $can_add, $can_edit, $can_delete, $image_root, $tree_state) ?>
	<? endforeach; ?>
<? endif; ?>
	</ul>
</div>

<div class="buttons">
<? if ($can_add): ?>
	<a class="positive" href="<?= $this->urlTo('/content/categories/add') ?>">Add New Base Category</a>
<? endif; ?>
</div>
<div class="clear"></div>

