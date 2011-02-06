<?php
	function outputThemes($theme, $level, $self, $canEdit, $imageBase)
	{
		$tabs = str_repeat("\t", $level);
		$themeID = $theme->id;
		$themeTitle = SparkView::escape_html($theme->title);
		$themeClass = empty($theme->children) ? ' no-children' : '';
		$childDisplayClass = ($level > 0) ? ' hidden' : '';

		$out = <<<EOD
		{$tabs}<li id="theme_{$themeID}">
			{$tabs}<div class="entry">
				{$tabs}<div class="column first{$themeClass}">
					{$tabs}<span>

EOD;
		if (!empty($theme->children))
		{
			$expand_collpase = ($level > 0) ? 'expand' : 'collapse';
			$out .= <<<EOD
						{$tabs}<a class="{$expand_collpase}" href=""></a>

EOD;
		}
		if ($canEdit)
		{
			$out .= <<<EOD
						{$tabs}<a href="/design/themes/edit/{$themeID}" title="{$themeTitle}"><img alt="theme-icon" class="icon" src="{$imageBase}theme.png" title="" /><span class="title">{$themeTitle}</span></a>

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
				$out .= outputThemes($child, $level, $self, $canEdit, $imageBase);
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

<? $plurality = !empty($theme->children) ? 's' : ''; ?>

<div class="title">
	Delete Theme<?= $plurality ?>
</div>

<div id="page-header">
	<ul>
		<li class="warning">Are you sure you want to permanently delete the following theme<?= $plurality ?> and all associated assets (templates, snippets, tags, styles, scripts, and images)?</li>
	</ul>
</div>
<div id="theme-list" class="hier-list">
	<ul class="level-0">
<?= outputThemes($theme, 0, $this, $can_edit, $image_root) ?>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="theme_id" value="<?= @$theme_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="delete">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Theme<?= $plurality ?>
		</button>
	</div>
	or <a href="<?= $this->urlTo('/design/themes') ?>">Cancel</a>
</form>
<div class="clear"></div>
