<? $this->render('form_top'); ?>

<div class="title">
	Delete Script
</div>

<? $script_name = $this->escape($script_name); ?>

<div id="page-header">
	<ul>
		<li class="warning">Are you sure you want to permanently delete script &ldquo;<?= $script_name ?>?&rdquo;</li>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="script_id" value="<?= @$script_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="delete">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Script &ldquo;<?= $script_name ?>&rdquo;
		</button>
	</div>
	or <a href="<?= $this->urlTo($can_edit ? '/design/scripts/edit/'.$script_id : '/design/scripts') ?>">Cancel</a>
</form>
<div class="clear"></div>
