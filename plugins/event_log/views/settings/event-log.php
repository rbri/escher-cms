<? switch ($action): ?>
<? 	case 'install': $this->render('/settings/event-log_install'); break; ?>
<? 	case 'view': $this->render('/settings/event-log_list'); break; ?>
<? 	case 'clear': $this->render('/settings/event-log_clear'); break; ?>
<? endswitch; ?>
