<? switch ($action): ?>
<? 	case 'add': $this->render('content/files_update', array('mode'=>'add')); break; ?>
<? 	case 'edit': $this->render('content/files_update', array('mode'=>'edit')); break; ?>
<? 	case 'delete': $this->render('content/files_delete'); break; ?>
<? endswitch; ?>
