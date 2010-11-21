<? switch ($action): ?>
<? 	case 'add': $this->render('design/scripts_update', array('mode'=>'add')); break; ?>
<? 	case 'edit': $this->render('design/scripts_update', array('mode'=>'edit')); break; ?>
<? 	case 'delete': $this->render('design/scripts_delete'); break; ?>
<? endswitch; ?>
