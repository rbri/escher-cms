<? if ($can_move): ?>
<script src="<?= $this->urlToStatic('/js/jquery.ui.nestedSortable.js') ?>" type="text/javascript"></script>
<? endif; ?>
<script type="text/javascript">
	$(document).ready(function() {

<? if ($can_move): ?>
		$('ol.sortable').nestedSortable({
			disableNesting: 'no-nest',
			forcePlaceholderSize: true,
			handle: 'div',
			helper:	'clone',
			items: 'li',
			maxLevels: 0,
			opacity: .6,
			placeholder: 'placeholder',
			revert: 250,
			tabSize: 25,
			tolerance: 'pointer',
			toleranceElement: '> div',
			listType: 'ol',
			
			update: function(event, ui) {
				if(ui.item.parents('#page_1').is('li') === false)
				{
					$(this).nestedSortable('cancel');
				}
				else
				{
					var order = $(this).nestedSortable('serialize');

					$.ajax({
						dataType: 'json',
						async: false,
						type: 'post',
						url: '<?= $order_pages_url ?>',
						data: order,
						cache: false,
						error: function(){
							$('#page-list ol.sortable').nestedSortable('cancel');
						},
						success: function(data, textStatus, jqXHR){
						//	ui.item.parent().parent().children('div.entry').filter(':first').children('div.column.first').children('span').children('a.expander').addClass('collapse');
							if (data['new_parent'])
							{
								window.location.reload(true);	// not terribly elegant, but the alternative is complicated and glitchy
							}
						},
					});  
				}
			},
			
		});
<? endif; ?>

		$('select.add_child').change(function(event) {
			var model_id = $(this).val();
			var url = "<?= $this->urlTo('/content/pages/add', true, true) ?>/" + model_id;
			if (model_id != 0)
			{
				$(this).val(0);
				window.location = url;
			}
		});
		
	});
</script>

<?php
	function outputPages($page, $level, $self, $modelNames, $canAdd, $canEdit, $imageBase, $treeState)
	{
		if (!$page)
		{
			return;
		}
		
		$tabs = str_repeat("\t", $level);
		$pageID = $page->id;
		$pageTitle = SparkView::escape_html($page->title);
		$pageAuthor = SparkView::escape_html($page->author_name);
		$pageClass = empty($page->children) ? ' no-children' : '';
		$childDisplayClass = isset($treeState[$pageID]) ? ($treeState[$pageID] ? ' hidden' : '') : (($level > 0) ? ' hidden' : '');

		$out = <<<EOD
		{$tabs}<li id="page_{$pageID}">
			{$tabs}<div class="entry">
				{$tabs}<div class="column first{$pageClass}">
					{$tabs}<span>

EOD;
		{
			if (empty($page->children))
			{
				$expand_collapse = '';
			}
			else
			{
				$expand_collapse = isset($treeState[$pageID]) ? ($treeState[$pageID] ? ' expand' : ' collapse') : (($level > 0) ? ' expand' : ' collapse');
			}
			$out .= <<<EOD
						{$tabs}<a class="expander{$expand_collapse}" href=""></a>

EOD;
		}
			$statusClass = strtolower($page->statusText()) . '-status';
			if ($canEdit)
			{
				$out .= <<<EOD
						{$tabs}<a href="{$self->urlTo("/content/pages/edit/{$pageID}")}" title="{$pageTitle}"><img alt="page-icon" class="icon" src="{$imageBase}page.png" title="" /><span class="title">{$pageTitle}</span></a>

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

EOD;
if ($canAdd):
			$out .= <<<EOD
				{$tabs}<div class="column action">
					{$tabs}<select class="add_child">
						{$tabs}<option value="0">Add Child</option>
						{$tabs}<option value="{$pageID}">&lt;default&gt;</option>
						{$tabs}<option value="{$pageID}/inherit">&lt;inherit&gt;</option>

EOD;
			foreach($modelNames as $id => $name)
			{
				$name = SparkView::escape_html($name);
				$out .= <<<EOD
						{$tabs}<option value="{$pageID}/{$id}">{$name}</option>

EOD;
			}
			$out .= <<<EOD
					{$tabs}</select>
				{$tabs}</div>

EOD;
endif;
			$out .= <<<EOD
				{$tabs}<div class="column status {$statusClass}">{$page->statusText()}</div>
				{$tabs}<div class="column author">{$pageAuthor}</div>
				{$tabs}<div class="column created">{$page->getDate('created')}</div>
			{$tabs}</div>

EOD;

		if ($children = $page->children)
		{
			++$level;

			$out .= <<<EOD
			{$tabs}<ol class="level-{$level} collapsible{$childDisplayClass}">

EOD;
			foreach ($children as $child)
			{
				$out .= outputPages($child, $level, $self, $modelNames, $canAdd, $canEdit, $imageBase, $treeState);
			}
			$out .= <<<EOD
			{$tabs}</ol>

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
	Pages
</div>

<div id="page-header">
	<ul>
		<li class="page">Page</li>
<? if ($can_add): ?>
		<li class="action">Action</li>
<? endif; ?>
		<li class="status">Status</li>
		<li class="author">Author</li>
		<li class="created">Created</li>
	</ul>
</div>

<div id="page-list" class="hier-list persistent">
	<ol class="level-0 sortable no-nest">
<?= $root_page ? outputPages($root_page, 0, $this, $model_names, $can_add, $can_edit, $image_root, $tree_state) : '<li class="no-entries">No Pages</li>' ?>
	</ol>
</div>

<? if (!$root_page): ?>
<div class="buttons">
<? if ($can_add): ?>
	<a class="positive" href="<?= $this->urlTo('/content/pages/add') ?>">Add New Page</a>
<? endif; ?>
</div>
<div class="clear"></div>
<? endif; ?>
