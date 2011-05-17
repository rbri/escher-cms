<? switch ($action): ?>
<? 	case 'edit': $this->render('design/branches_update'); break; ?>
<? 	case 'push': $this->render('design/branches_push'); break; ?>
<? 	case 'rollback': $this->render('design/branches_rollback'); break; ?>
<? 	case 'list': $this->render('design/branches_list'); break; ?>
<? endswitch; ?>
