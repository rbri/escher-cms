<? switch ($action): ?>
<? 	case 'add': $this->render('content/images_update', array('mode'=>'add')); break; ?>
<? 	case 'edit': $this->render('content/images_update', array('mode'=>'edit')); break; ?>
<? 	case 'delete': $this->render('content/images_delete'); break; ?>
<? endswitch; ?>
