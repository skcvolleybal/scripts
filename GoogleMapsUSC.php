<style>
#map-canvas {
  height: 400px;
}
</style>
<?php 
$env = json_decode(file_get_contents("../../env.json"));


<div id="map-canvas"></div>
<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?php echo $env->GoogleMapsApiKey; ?>&sensor=true"></script>
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
