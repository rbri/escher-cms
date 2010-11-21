<div id="footer">
	<ul id="footernav">
<? if (!empty($logged_in)): ?>
		<li><a href="<?= $this->urlTo('/settings/logout') ?>">Log Out</a></li>
<? endif; ?>
		<li><a href="<?= $site_url ?>" target="_blank">View Site</a></li>
	</ul>
	<p><em>Powered by <a href="http://eschercms.org">Escher CMS</a></em></p>
	<br />
	<p>Escher Content Management System v<?= $escher_version ?></p>
	<p>Copyright 2009-<?= date('Y'); ?> Sam Weiss. All Rights Reserved.</p>
</div>
