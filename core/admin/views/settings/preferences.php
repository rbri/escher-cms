<? $this->render('form_top'); ?>

<div class="title">
	Preferences
</div>

<form method="post" action="">
	<div class="form-area centered">
		<div class="fields">
			<ul id="prefs" >
<? foreach($prefstabs as $prefstab): ?>
				<li<?= ($prefstab === $selected_prefstab) ? ' class="selected"' : '' ?>><a href="<?= $this->urlTo('/settings/preferences/'.strtolower($prefstab)) ?>"><?= ucwords(str_replace('-', ' ', $prefstab)) ?></a></li>
<? endforeach; ?>
			</ul>
<? foreach($prefs as $section => $sectionPrefs): ?>
			<div class="header"><?= $this->escape(ucwords(SparkInflector::humanize(preg_replace('/^\d+/', '', $section)))) ?></div>
<? foreach($sectionPrefs as $key => $pref): ?><? $prefName = $this->escape($pref['name']); $prefHelp = $this->escape($lang->get($pref['name'].'_help', '')); ?>
			<div class="row">
				<span class="label"><label<?= isset($errors[$pref['name']]) ? ' class="error"' : '' ?> for="<?= $prefName ?>"><?= $this->escape($lang->get($prefName)) ?>:</label></span>
				<span class="field"><?= call_user_func($callbacks[$pref['type']], $pref) ?></span>
<? if (!empty($prefHelp)): ?>
				<img class="helptip" alt="Help" title="<?= $prefHelp ?>" src="<?= $image_root.'info.png' ?>" />
<? endif; ?>
			</div>
<? endforeach; ?>
			<div class="spacer"></div>
<? endforeach; ?>
		</div>
	</div>
<? if (!empty($prefstabs)): ?>
	<div class="buttons">
		<button class="positive" type="submit" name="save">
			<img src="<?= $image_root.'tick.png' ?>" alt="" />
			Save Changes
		</button>
	</div>
<? endif; ?>
</form>
<div class="clear"></div>
