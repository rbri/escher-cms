	<ul id="navbar">
<? foreach($tabs as $tab): ?>
		<li<?= ($tab === $selected_tab) ? ' class="selected"' : '' ?>><a href="<?= $this->urlTo('/'.strtolower($tab)) ?>"><?= ucwords(str_replace('-', ' ', $tab)) ?></a></li>
<? endforeach; ?>
	</ul>
	<ul id="subnav">
	<? foreach($subtabs as $subtab): ?>
		<li<?= ($subtab === $selected_subtab) ? ' class="selected"' : '' ?>><a href="<?= $this->urlTo('/'.strtolower($selected_tab).'/'.strtolower($subtab)) ?>"><?= ucwords(str_replace('-', ' ', $subtab)) ?></a></li>
	<? endforeach; ?>
	</ul>
