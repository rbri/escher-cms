<? switch ($action): ?>
<? 	case 'add': $this->render('content/categories_update', array('mode'=>'add')); break; ?>
<? 	case 'edit': $this->render('content/categories_update', array('mode'=>'edit')); break; ?>
<? 	case 'delete': $this->render('content/categories_delete'); break; ?>
<? 	case 'list': $this->render('content/categories_list'); break; ?>
<? endswitch; ?>
