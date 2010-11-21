<? switch ($action): ?>
<? 	case 'add': $this->render('settings/users_update', array('mode'=>'add')); break; ?>
<? 	case 'edit': $this->render('settings/users_update', array('mode'=>'edit')); break; ?>
<? 	case 'delete': $this->render('settings/users_delete'); break; ?>
<? 	case 'list': $this->render('settings/users_list'); break; ?>
<? endswitch; ?>
