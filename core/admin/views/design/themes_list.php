<?php
	function outputThemes($theme, $level, $self, $canAdd, $canEdit, $canDelete, $imageBase, $treeState)
	{
		if (!$theme)
		{
			return;
		}
		
		$tabs = str_repeat("\t", $level);
		$themeID = $theme->id;
		$themeTitle = SparkView::escape_html($theme->title);
		$themeClass = empty($theme->children) ? ' no-children' : '';
		$childDisplayClass = isset($treeState[$themeID]) ? ($treeState[$themeID] ? ' hidden' : '') : (($level > 0) ? ' hidden' : '');

		$out = <<<EOD
		{$tabs}<li id="theme_{$themeID}">
			{$tabs}<div class="entry">
				{$tabs}<div class="column first{$themeClass}">
					{$tabs}<span>

EOD;
		if (!empty($theme->children))
		{
			$expand_collpase = isset($treeState[$themeID]) ? ($treeState[$themeID] ? 'expand' : 'collapse') : (($level > 0) ? 'expand' : 'collapse');
			$out .= <<<EOD
						{$tabs}<a class="{$expand_collpase}" href=""></a>

EOD;
		}
		if ($canEdit)
		{
			$out .= <<<EOD
						{$tabs}<a href="{$self->urlTo("/design/themes/edit/{$themeID}")}" title="{$themeTitle}"><img alt="theme-icon" class="icon" src="{$imageBase}theme.png" title="" /><span class="title">{$themeTitle}</span></a>

EOD;
		}
		else
		{
			$out .= <<<EOD
						{$tabs}<img alt="theme-icon" class="icon" src="{$imageBase}theme.png" title="" /><span class="title">{$themeTitle}</span>

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
				{$tabs}<a href="{$self->urlTo("/design/themes/add/{$themeID}")}"><img alt="add child" title="add child" src="{$imageBase}plus.png" /></a>&nbsp;

EOD;
}
if ($canDelete)
{
			$out .= <<<EOD
				{$tabs}<a href="{$self->urlTo("/design/themes/delete/{$themeID}")}"><img title="delete theme" alt="delete theme" src="{$imageBase}minus.png" /></a>

EOD;
}
			$out .= <<<EOD
			{$tabs}</div>
			{$tabs}<div class="column author">{$theme->author_name}</div>
			{$tabs}<div class="column created">{$theme->getDate('created')}</div>
		{$tabs}</div>

EOD;

		if ($children = $theme->children)
		{
			++$level;
			
			$out .= <<<EOD
			{$tabs}<ul class="level-{$level} collapsible{$childDisplayClass}">

EOD;
			foreach ($children as $child)
			{
				$out .= outputThemes($child, $level, $self, $canAdd, $canEdit, $canDelete, $imageBase, $treeState);
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
	Themes
</div>

<div id="page-header">
	<ul>
		<li class="theme">Theme</li>
		<li class="action">Action</li>
		<li class="author">Author</li>
		<li class="created">Created</li>
	</ul>
</div>
<div id="theme-list" class="hier-list persistent">
	<ul class="level-0">
<? if (empty($themes)): ?>
	<li class="no-entries">No Themes</li>
<? else: ?>
	<? foreach ($themes as $theme): ?>
		<?= outputThemes($theme, 0, $this, $can_add, $can_edit, $can_delete, $image_root, $tree_state) ?>
	<? endforeach; ?>
<? endif; ?>
	</ul>
</div>

<div class="buttons">
<? if ($can_add): ?>
	<a class="positive" href="<?= $this->urlTo('/design/themes/add') ?>">Add New Base Theme</a>
<? endif; ?>
</div>
<div class="clear"></div>
