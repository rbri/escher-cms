<? $this->render('alert'); ?>

<div class="title">
	Branches
</div>

<div id="page-header">
	<ul>
		<li class="branch">Branch</li>
		<li class="action">Action</li>
	</ul>
</div>
<div class="hier-list">
	<ul class="level-0">
<? if (empty($branches)): ?>
	<li class="no-entries">No Branches</li>
<? else: ?>
	<? $odd = false; krsort($branches); foreach($branches as $id => $branch): ?>
		<li id="branch_<?= $id ?>">
			<div class="entry">
				<div class="column first no-children">
					<?= ($branch->id != 1 && $can_manage) ? '<a href="' . $this->urlTo("/settings/branches/edit/{$id}") . '" title="Branch">' : '' ?><img alt="branch-icon" class="icon" src="<?= $image_root.'branch.png' ?>" title="" /><span class="title"><?= $this->escape($branch->name) ?></span><?= ($branch->id != 1 && $can_manage) ? '</a>' : '' ?>
				</div>
				<div class="column action">
<? if ($branch->id != 1 && $can_push): ?>
					<a href="<?= $this->urlTo("/settings/branches/push/{$id}") ?>"><img title="push branch" alt="push branch" src="<?= $image_root.'down-arrow.png' ?>" /></a>
<? endif; ?>
<? if ($branch->id != 3 && $can_rollback): ?>
<? $rb = $id + 1; ?>
					<a href="<?= $this->urlTo("/settings/branches/rollback/{$rb}") ?>"><img title="rollback branch" alt="rollback branch" src="<?= $image_root.'up-arrow.png' ?>" /></a>
<? endif; ?>
				</div>
			</div>
		</li>
	<? endforeach; ?>
<? endif; ?>
	</ul>
</div>

<div class="clear"></div>
