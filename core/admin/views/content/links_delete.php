<? $this->render('form_top'); ?>

<div class="title">
	Delete Link
</div>

<? $link_name = $this->escape($link_name); ?>

<div id="page-header">
	<ul>
		<li class="warning">Are you sure you want to permanently delete link &ldquo;<?= $link_name ?>?&rdquo;</li>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="link_id" value="<?= @$link_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="delete">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Link &ldquo;<?= $link_name ?>&rdquo;
		</button>
	</div>
	or <a href="<?= $this->urlTo($can_edit ? '/content/links/edit/'.$link_id : '/content/links') ?>">Cancel</a>
</form>
<div class="clear"></div>
