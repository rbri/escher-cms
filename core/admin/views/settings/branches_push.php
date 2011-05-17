<? $this->render('form_top'); ?>

<div class="title">
	Push Branch
</div>

<?
	$branch_name = $this->escape($branch_name);
	$to_branch_name = $this->escape($to_branch_name);
?>

<div id="page-header">
	<ul>
		<li class="warning">
			Are you sure you want to permanently push branch &ldquo;<?= $branch_name ?>&rdquo; to &ldquo;<?= $to_branch_name ?>?&rdquo;
			This action will overwrite the contents of the &ldquo;<?= $to_branch_name ?>&rdquo; branch.
			This action cannot be undone.
		</li>
	</ul>
</div>

<form method="post" action="">
	<input type="hidden" name="branch_id" value="<?= @$branch_id ?>" />
	&nbsp;
	<div class="buttons">
		<button class="positive" type="submit" name="push">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Push Branch &ldquo;<?= $branch_name ?>&rdquo;
		</button>
	</div>
	or <a href="<?= $this->urlTo('/settings/branches') ?>">Cancel</a>
</form>
<div class="clear"></div>
