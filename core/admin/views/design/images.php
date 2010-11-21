<? switch ($action): ?>
<? 	case 'add': $this->render('design/images_update', array('mode'=>'add')); break; ?>
<? 	case 'edit': $this->render('design/images_update', array('mode'=>'edit')); break; ?>
<? 	case 'delete': $this->render('design/images_delete'); break; ?>
<? endswitch; ?>
