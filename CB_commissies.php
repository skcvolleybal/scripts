<style>

</style><?php
  jimport( 'joomla.access.access' );
  
  $user_id = $jumi[0];
  
  $groups = JAccess::getGroupsByUser($user_id);
  $groupid_list = '(' . implode(',', $groups) . ')'; 
  
  $db = JFactory::getDBO();
  $query = $db->getQuery(true);
  $query->select('id, title');
  $query->from('#__usergroups');
  $query->where('id IN ' .$groupid_list);
  $db->setQuery($query);
  $rows = $db->loadAssocList();
  
  if (count($rows) == 0){
    return;
  }
  
  $result = "";
  foreach ($rows as $row){
    $name = "";
    switch ($row['title']){
      case "Accie":
        $link = "/index.php/commissies/commissies-a-e/accie";
        $name = $row['title'];
        break;
      case "Almanakcie":
        $link = "/index.php/commissies/commissies-a-e/almanakcie";
        $name = $row['title'];
        break;
      case "Barcie":
        $link = "/index.php/commissies/commissies-a-e/barcie";
        $name = $row['title'];
        break;
      case "Batacie":
        $link = "/index.php/commissies/commissies-a-e/batacie";
        $name = $row['title'];
        break;
      case "Beachcie":
        $link = "/index.php/commissies/commissies-a-e/beachcie";
        $name = $row['title'];
        break;
      case "Bucie":
        $link = "/index.php/commissies/commissies-a-e/bucie";
        $name = $row['title'];
        break;
      case "ComA":
        $link = "/index.php/commissies/commissies-a-e/coma";
        $name = $row['title'];
        break;
      case "Cuco":
        $link = "/index.php/commissies/commissies-a-e/cuco";
        $name = $row['title'];
        break;
      case "EJC":
        $link = "/index.php/commissies/commissies-a-e/ejc";
        $name = $row['title'];
        break;
      case "Kasco":
        $link = "/index.php/commissies/commissies-k-w/kasco";
        $name = $row['title'];
        break;
      case "Lustrumcie":
        $link = "/index.php/commissies/commissies-k-w/lustrumcie";
        $name = $row['title'];
        break;
      case "Nachtcie":
        $link = "/index.php/commissies/commissies-k-w/nachtcie";
        $name = $row['title'];
        break;
      case "Promocie":
        $link = "/index.php/commissies/commissies-k-w/promocie";
        $name = $row['title'];
        break;
      case "TC":
        $link = "/index.php/commissies/commissies-k-w/technische-commissie";
        $name = $row['title'];
        break;
      case "Tripcie":
        $link = "/index.php/commissies/commissies-k-w/tripcie";
        $name = $row['title'];
        break;
      case "Webcie":
        $link = "/index.php/commissies/commissies-k-w/webcie";
        $name = $row['title'];
      default:
        break;
    }
    if ($name != ""){
      $result .= "<a href='$link'><img style='display: inline;' class='commissie' src='/images/images/Commissies/Commissie-icons/$name.png' alt='$name' \></a>";
      $name = "";
    }
    
  }
  if ($result != ""){
    echo "<div class='commissies'>$result</div>";
  }
  
  
?>
