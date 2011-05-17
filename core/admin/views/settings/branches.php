<? switch ($action): ?>
<? 	case 'edit': $this->render('settings/branches_update'); break; ?>
<? 	case 'push': $this->render('settings/branches_push'); break; ?>
<? 	case 'rollback': $this->render('settings/branches_rollback'); break; ?>
<? 	case 'list': $this->render('settings/branches_list'); break; ?>
<? endswitch; ?>
