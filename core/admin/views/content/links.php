<? switch ($action): ?>
<? 	case 'add': $this->render('content/links_update', array('mode'=>'add')); break; ?>
<? 	case 'edit': $this->render('content/links_update', array('mode'=>'edit')); break; ?>
<? 	case 'delete': $this->render('content/links_delete'); break; ?>
<? endswitch; ?>
