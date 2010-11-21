<? $this->render('form_top'); ?>

<div class="title">
	Delete Image
</div>

<? $image_name = $this->escape($image_name); ?>

<div id="page-header">
	<ul>
		<li class="warning">Are you sure you want to permanently delete image &ldquo;<?= $image_name ?>?&rdquo;</li>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="image_id" value="<?= @$image_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="delete">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Image &ldquo;<?= $image_name ?>&rdquo;
		</button>
	</div>
	or <a href="<?= $this->urlTo($can_edit ? '/design/images/edit/'.$image_id : '/design/images') ?>">Cancel</a>
</form>
<div class="clear"></div>
