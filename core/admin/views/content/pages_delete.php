<?php
	function outputPages($page, $level, $self, $canEdit, $imageBase)
	{
		$tabs = str_repeat("\t", $level);
		$pageID = $page->id;
		$pageTitle = SparkView::escape_html($page->title);
		$pageClass = empty($page->children) ? ' no-children' : '';
		$childDisplayClass = ($level > 0) ? ' hidden' : '';

		$out = <<<EOD
		{$tabs}<li id="page_{$pageID}">
			{$tabs}<div class="entry">
				{$tabs}<div class="column first{$pageClass}">
					{$tabs}<span>

EOD;
		if (!empty($page->children))
		{
			$expand_collpase = ($level > 0) ? 'expand' : 'collapse';
			$out .= <<<EOD
						{$tabs}<a class="{$expand_collpase}" href=""></a>

EOD;
		}
		if ($canEdit)
		{
			$out .= <<<EOD
						{$tabs}<a href="/content/pages/edit/{$pageID}" title="{$pageTitle}"><img alt="page-icon" class="icon" src="{$imageBase}page.png" title="" /><span class="title">{$pageTitle}</span></a>

EOD;
		}
		else
		{
			$out .= <<<EOD
						{$tabs}<img alt="page-icon" class="icon" src="{$imageBase}page.png" title="" /><span class="title">{$pageTitle}</span>

EOD;
		}
			$out .= <<<EOD
						{$tabs}<img alt="" class="busy" src="{$imageBase}spinner.gif" style="display: none;" title="" />
					{$tabs}</span>
				{$tabs}</div>
			{$tabs}</div>

EOD;

		if ($children = $page->children)
		{
			++$level;
			
			$out .= <<<EOD
			{$tabs}<ul class="level-{$level} collapsible{$childDisplayClass}">

EOD;
			foreach ($children as $child)
			{
				$out .= outputPages($child, $level, $self, $canEdit, $imageBase);
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

<? $plurality = !empty($root_page->children) ? 's' : ''; ?>

<div class="title">
	Delete Page<?= $plurality ?>
</div>

<div id="page-header">
	<ul>
		<li class="warning">Are you sure you want to permanently delete the following page<?= $plurality ?>?</li>
	</ul>
</div>
<div id="page-list" class="hier-list">
	<ul class="level-0">
<?= outputPages($root_page, 0, $this, $can_edit, $image_root) ?>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="page_id" value="<?= @$page_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="delete">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Page<?= $plurality ?>
		</button>
	</div>
	or <a href="<?= $this->urlTo($can_edit ? '/content/pages/edit/'.$page_id : '/content/pages') ?>">Cancel</a>
</form>
<div class="clear"></div>
