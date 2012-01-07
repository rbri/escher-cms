<? $this->render('alert'); ?>

<div class="title">
	View Event Log
</div>

<?= $pagination = $this->renderPagination(array('class'=>'event-pagination', 'wrapper'=>'', 'list'=>'ol', 'class_active'=>'selected', 'always_show_labels'=>false, 'label_prev'=>'&larr; Newer', 'label_next'=>'Older &rarr;', 'page_url'=>$page_url, 'cur_page'=>$cur_page, 'last_page'=>$last_page,), true); ?>
<table id="event-list">
	<thead>
		<tr>
			<th>When</th>
			<th>What</th>
			<th>Who</th>
		</tr>
	</thead>
	<tbody>
<? if (empty($events)): ?>
		<tr><td colspan="3"></td></tr>
		<tr><td colspan="3" style="text-align:center;"><em>No Events</em></td></tr>
<? else: ?>
<? $odd = false; ?>
<? foreach ($events as $event): ?>
		<tr class="row <?= ($odd = !$odd) ? 'odd' : 'even' ?>"><td><?= $this->escape($event->when) ?></td><td><?= $this->escape($event->what) ?></td><td><?= $this->escape($event->who) ?></td></tr>
<? endforeach; ?>
<? endif; ?>
	</tbody>
</table>
<?= $pagination ?>

<div class="buttons">
<? if ($can_clear): ?>
		<a class="negative" href="<?= $this->urlTo('/settings/event-log/clear') ?>">
			<img src="<?= $image_root.'cross.png' ?>" alt="" />
			Clear Event Log
		</a>
<? endif; ?>
</div>
<div class="clear"></div>
