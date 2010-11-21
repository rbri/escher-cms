<? $this->render('form_top'); ?>

<div class="title">
	Delete File
</div>

<? $file_name = $this->escape($file_name); ?>

<div id="page-header">
	<ul>
		<li class="warning">Are you sure you want to permanently delete file &ldquo;<?= $file_name ?>?&rdquo;</li>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="file_id" value="<?= @$file_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="delete">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete File &ldquo;<?= $file_name ?>&rdquo;
		</button>
	</div>
	or <a href="<?= $this->urlTo($can_edit ? '/content/files/edit/'.$file_id : '/content/files') ?>">Cancel</a>
</form>
<div class="clear"></div>
