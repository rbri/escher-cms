<? switch ($action): ?>
<? 	case 'add': $this->render('design/templates_update', array('mode'=>'add')); break; ?>
<? 	case 'edit': $this->render('design/templates_update', array('mode'=>'edit')); break; ?>
<? 	case 'delete': $this->render('design/templates_delete'); break; ?>
<? endswitch; ?>
