<?php
  $form_url = $jumi[0];
  if (isset($jumi[1])){
    $cm = $jumi[1];
  }
  else {
    $cm = 30;
  }
  
  $file_headers = @get_headers($form_url);
  if($file_headers[0] == 'HTTP/1.1 404 Not Found') {
    echo "De pagina '$form_url' bestaat niet. Verander de link naar iets wat wel bestaat";
  }
  else {
    if (strpos($form_url, '?') !== false) {
      echo "<iframe src=\"$form_url&embedded=true\" style=\"border: 0; width:100%; height:" . $cm . "cm\">Loading...</iframe>";
    }
    else {        
      echo "<iframe src=\"$form_url?embedded=true\" style=\"border: 0; width:100%; height:" . $cm . "cm\">Loading...</iframe>";
    }
  }
?>
