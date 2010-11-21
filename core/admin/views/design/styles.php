<? switch ($action): ?>
<? 	case 'add': $this->render('design/styles_update', array('mode'=>'add')); break; ?>
<? 	case 'edit': $this->render('design/styles_update', array('mode'=>'edit')); break; ?>
<? 	case 'delete': $this->render('design/styles_delete'); break; ?>
<? endswitch; ?>
