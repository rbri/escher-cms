<? $this->render('form_top'); ?>

<div class="title">
	Delete Block
</div>

<? $block_name = $this->escape($block_name); ?>

<div id="page-header">
	<ul>
		<li class="warning">Are you sure you want to permanently delete block &ldquo;<?= $block_name ?>?&rdquo;</li>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="block_id" value="<?= @$block_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="delete">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Block &ldquo;<?= $block_name ?>&rdquo;
		</button>
	</div>
	or <a href="<?= $this->urlTo($can_edit ? '/content/blocks/edit/'.$block_id : '/content/blocks') ?>">Cancel</a>
</form>
<div class="clear"></div>
