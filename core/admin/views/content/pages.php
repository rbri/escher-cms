<? switch ($action): ?>
<? 	case 'add': $this->render('content/pages_update', array('mode'=>'add')); break; ?>
<? 	case 'edit': $this->render('content/pages_update', array('mode'=>'edit')); break; ?>
<? 	case 'delete': $this->render('content/pages_delete'); break; ?>
<? 	case 'list': $this->render('content/pages_list'); break; ?>
<? endswitch; ?>
