<? switch ($action): ?>
<? 	case 'add': $this->render('content/models_update', array('mode'=>'add')); break; ?>
<? 	case 'edit': $this->render('content/models_update', array('mode'=>'edit')); break; ?>
<? 	case 'delete': $this->render('content/models_delete'); break; ?>
<? 	case 'list': $this->render('content/models_list'); break; ?>
<? endswitch; ?>
