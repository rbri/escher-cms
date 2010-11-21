<? switch ($action): ?>
<? 	case 'add': $this->render('settings/roles_update', array('mode'=>'add')); break; ?>
<? 	case 'edit': $this->render('settings/roles_update', array('mode'=>'edit')); break; ?>
<? 	case 'delete': $this->render('settings/roles_delete'); break; ?>
<? 	case 'list': $this->render('settings/roles_list'); break; ?>
<? endswitch; ?>
