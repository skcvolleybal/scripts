<style>
#map-canvas {
  height: 400px;
}
</style>
<?php 
require 'vendor/autoload.php';

$env = json_decode(file_get_contents("../../env.json"));
$envG = $env->GoogleMapsApiKey->key;

\Sentry\init(['dsn' => 'https://45df5d88f1084fcd96c8ae9fa7db50c7@o4504883122143232.ingest.sentry.io/4504883124240384',
'environment' => $env->Environment ]);


?>


<div id="map-canvas"></div>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?php echo $envG; ?>&sensor=true"></script>
<script type="text/javascript">
  function initialize() {
    var myLatlng = new google.maps.LatLng(52.166249, 4.461262);
    var mapOptions = {
      center: myLatlng,
      zoom: 17
    };
    
    var map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);
    
    var marker = new google.maps.Marker({
      position: myLatlng,
      map: map,
    });
  }
  google.maps.event.addDomListener(window, 'load', initialize);
</script>
