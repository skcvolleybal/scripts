<?php
  $db = JFactory::getDbo();
  jimport( 'joomla.access.access' );
  
  $commissie = $jumi[0];
  
  if (isset($jumi[1])){
    $voorzitter = $jumi[1];
    $titel = "Voorzitter";
  }
  else if (isset($jumi[2])){
    $voorzitter2 = $jumi[2];
    $titel = "Co-Voorzitter";
  }
  else {
    $voorzitter = "";
  }
  
  $query = $db->getQuery(true);
  $query->select('id, title')
        ->from('#__usergroups')
        ->where('title = \'' . $commissie . '\'');
  $db->setQuery($query);
  $results = $db->loadAssoc();
  $group_id = $results['id'];
  
  $user_ids = JAccess::getUsersByGroup($group_id);
  
  if (count($user_ids) == 0){
    echo "Geen mensen in de commissie: '" . $commissie . "'";
    return;
  }
  
  $list = "";
  foreach ($user_ids as $user_id){
    $user = JFactory::getUser($user_id);
    if (strtolower($user->name) == strtolower($voorzitter)){
      $list = "<li>" . $user->name . " <i>(" . $titel . ")</i></li>" . $list;
    }
    else if (strtolower($user->name) == strtolower($voorzitter2)){
      $list = "<li>" . $user->name . " <i>(" . $titel . ")</i></li>" . $list;
    }
    else {
      $list .= "<li>" . $user->name . "</li>";
    }
  }
  echo "De volgende mensen zitten in de $commissie:<br><ul>$list</ul>";
?>
