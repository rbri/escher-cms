<? switch ($action): ?>
<? 	case 'add': $this->render('content/blocks_update', array('mode'=>'add')); break; ?>
<? 	case 'edit': $this->render('content/blocks_update', array('mode'=>'edit')); break; ?>
<? 	case 'delete': $this->render('content/blocks_delete'); break; ?>
<? endswitch; ?>
