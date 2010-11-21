<? $this->render('alert'); ?>

<div class="title">
	Models
</div>

<div id="page-header">
	<ul>
		<li class="model">Model</li>
		<li class="action">Action</li>
	</ul>
</div>
<div class="hier-list">
	<ul class="level-0">
<? if (empty($models)): ?>
	<li class="no-entries">No Models</li>
<? else: ?>
	<? foreach ($models as $model): ?>
		<li id="model_<?= $model->id ?>">
			<div class="entry">
				<div class="column first no-children">
					<?= $can_edit ? ('<a href="' . $this->urlTo("/content/models/edit/{$model->id}") . '">') : '' ?><img alt="model-icon" class="icon" src="<?= $image_root.'model.png' ?>" title="" /><span class="title"><?= $this->escape($model->name) ?></span><?= $can_edit ? '</a>' : '' ?>
				</div>
				<div class="column action">
<? if ($can_delete) : ?>
					<a href="<?= $this->urlTo("/content/models/delete/{$model->id}") ?>"><img title="delete model" alt="delete model" src="<?= $image_root.'minus.png' ?>" /></a>
<? endif; ?>
				</div>
			</div>
		</li>
	<? endforeach; ?>
<? endif; ?>
	</ul>
</div>

<div class="buttons">
<? if ($can_add): ?>
	<a class="positive" href="<?= $this->urlTo('/content/models/add') ?>">Add New Model</a>
<? endif; ?>
</div>
<div class="clear"></div>
