<? $this->render('form_top'); ?>

<div class="title">
	Delete Style
</div>

<? $style_name = $this->escape($style_name); ?>

<div id="page-header">
	<ul>
		<li class="warning">Are you sure you want to permanently delete style &ldquo;<?= $style_name ?>?&rdquo;</li>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="style_id" value="<?= @$style_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="delete">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Style &ldquo;<?= $style_name ?>&rdquo;
		</button>
	</div>
	or <a href="<?= $this->urlTo($can_edit ? '/design/styles/edit/'.$style_id : '/design/styles') ?>">Cancel</a>
</form>
<div class="clear"></div>
