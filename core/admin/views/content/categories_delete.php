<?php
	function outputCategories($category, $level, $self, $canEdit, $imageBase)
	{
		$tabs = str_repeat("\t", $level);
		$categoryID = $category->id;
		$categoryTitle = SparkView::escape_html($category->title);
		$categoryClass = empty($category->children) ? ' no-children' : '';
		$childDisplayClass = ($level > 0) ? ' hidden' : '';

		$out = <<<EOD
		{$tabs}<li id="category_{$categoryID}">
			{$tabs}<div class="entry">
				{$tabs}<div class="column first{$categoryClass}">
					{$tabs}<span>

EOD;
		if (!empty($category->children))
		{
			$expand_collpase = ($level > 0) ? 'expand' : 'collapse';
			$out .= <<<EOD
						{$tabs}<a class="{$expand_collpase}" href=""></a>

EOD;
		}
		if ($canEdit)
		{
			$out .= <<<EOD
						{$tabs}<a href="/content/categories/edit/{$categoryID}" title="{$categoryTitle}"><img alt="category-icon" class="icon" src="{$imageBase}category.png" title="" /><span class="title">{$categoryTitle}</span></a>

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
				$out .= outputCategories($child, $level, $self, $canEdit, $imageBase);
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

<? $this->render('form_top'); ?>

<? $objectName = !empty($category->children) ? 'Categories' : 'Category'; ?>

<div class="title">
	Delete <?= $objectName ?>
</div>

<div id="page-header">
	<ul>
		<li class="warning">Are you sure you want to permanently delete the following <?= strtolower($objectName) ?>?</li>
	</ul>
</div>
<div id="cat-list" class="hier-list">
	<ul class="level-0">
<?= outputCategories($category, 0, $this, $can_edit, $image_root) ?>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="category_id" value="<?= @$category_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="delete">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete <?= $objectName ?>
		</button>
	</div>
	or <a href="<?= $this->urlTo('/content/categories') ?>">Cancel</a>
</form>
<div class="clear"></div>
