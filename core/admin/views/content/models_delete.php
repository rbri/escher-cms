<? $this->render('form_top'); ?>

<div class="title">
	Delete Model
</div>

<? $model_name = $this->escape($model_name); ?>

<div id="page-header">
	<ul>
		<li class="warning">Are you sure you want to permanently delete model &ldquo;<?= $model_name ?>?&rdquo;</li>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="model_id" value="<?= @$model_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="delete">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Delete Model &ldquo;<?= $model_name ?>&rdquo;
		</button>
	</div>
	or <a href="<?= $this->urlTo($can_edit ? '/content/models/edit/'.$model_id : '/content/models') ?>">Cancel</a>
</form>
<div class="clear"></div>
