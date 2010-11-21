<? switch ($action): ?>
<? 	case 'add': $this->render('design/snippets_update', array('mode'=>'add')); break; ?>
<? 	case 'edit': $this->render('design/snippets_update', array('mode'=>'edit')); break; ?>
<? 	case 'delete': $this->render('design/snippets_delete'); break; ?>
<? endswitch; ?>
