<? switch ($action): ?>
<? 	case 'add': $this->render('design/themes_update', array('mode'=>'add')); break; ?>
<? 	case 'edit': $this->render('design/themes_update', array('mode'=>'edit')); break; ?>
<? 	case 'delete': $this->render('design/themes_delete'); break; ?>
<? 	case 'list': $this->render('design/themes_list'); break; ?>
<? endswitch; ?>
