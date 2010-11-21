<? $this->render('form_top'); ?>

<div class="title">
	Delete Tag
</div>

<? $tag_name = $this->escape($tag_name); ?>

<div id="page-header">
	<ul>
		<li class="warning">Are you sure you want to permanently delete tag &ldquo;<?= $tag_name ?>?&rdquo;</li>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="tag_id" value="<?= @$tag_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="delete">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Tag &ldquo;<?= $tag_name ?>&rdquo;
		</button>
	</div>
	or <a href="<?= $this->urlTo($can_edit ? '/design/tags/edit/'.$tag_id : '/design/tags') ?>">Cancel</a>
</form>
<div class="clear"></div>
