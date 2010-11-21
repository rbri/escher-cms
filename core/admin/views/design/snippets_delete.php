<? $this->render('form_top'); ?>

<div class="title">
	Delete Snippet
</div>

<? $snippet_name = $this->escape($snippet_name); ?>

<div id="page-header">
	<ul>
		<li class="warning">Are you sure you want to permanently delete snippet &ldquo;<?= $snippet_name ?>?&rdquo;</li>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="snippet_id" value="<?= @$snippet_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="delete">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Snippet &ldquo;<?= $snippet_name ?>&rdquo;
		</button>
	</div>
	or <a href="<?= $this->urlTo($can_edit ? '/design/snippets/edit/'.$snippet_id : '/design/snippets') ?>">Cancel</a>
</form>
<div class="clear"></div>
