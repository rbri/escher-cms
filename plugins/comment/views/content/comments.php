<? switch ($action): ?>
<? 	case 'install': $this->render('/content/comments_install'); break; ?>
<? 	case 'view': $this->render('/content/comments_list'); break; ?>
<? 	case 'moderate': $this->render('/content/comments_moderate'); break; ?>
<? 	case 'delete': $this->render('/content/comments_delete'); break; ?>
<? endswitch; ?>
