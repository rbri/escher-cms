<? $this->render('form_top'); ?>

<div class="title">
	Rollback Branch
</div>

<? $branch_name = $this->escape($branch_name); ?>

<div id="page-header">
	<ul>
		<li class="warning">Are you sure you want to permanently rollback branch &ldquo;<?= $branch_name ?>?&rdquo; This action will revert this branch to the current state of its parent branch. All modifications to this branch will be lost. This action cannot be undone.</li>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="branch_id" value="<?= @$branch_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="negative" type="submit" name="rollback">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Rollback Branch &ldquo;<?= $branch_name ?>&rdquo;
		</button>
	</div>
	or <a href="<?= $this->urlTo('/settings/branches') ?>">Cancel</a>
</form>
<div class="clear"></div>
