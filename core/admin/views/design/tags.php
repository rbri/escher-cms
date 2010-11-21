<? switch ($action): ?>
<? 	case 'add': $this->render('design/tags_update', array('mode'=>'add')); break; ?>
<? 	case 'edit': $this->render('design/tags_update', array('mode'=>'edit')); break; ?>
<? 	case 'delete': $this->render('design/tags_delete'); break; ?>
<? endswitch; ?>
