var map;

function success(position) {
	var s = document.querySelector('#status');
 
	if (s.className == 'success') {
		// not sure why we're hitting this twice in FF, I think it's to do with a cached result coming back    
		return;
	}
  
	s.innerHTML = "Мы нашли Вас!";
	s.className = 'success';
	lat = position.coords.latitude;
	lon = position.coords.longitude;
	drawMap(lat, lon, 18)
	
	var blueIcon = new GIcon(G_DEFAULT_ICON);
	blueIcon.image = "http://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png";
                
	// Set up our GMarkerOptions object
	markerOptions = {
		icon:blueIcon
	};


	var point = new GLatLng(lat,lon);
	map.addOverlay(new GMarker(point, markerOptions));
	
}

function error(msg) {
	var s = document.querySelector('#status');
	s.innerHTML = typeof msg == 'string' ? msg : "failed";
	s.className = 'fail';
  
// console.log(arguments);
}

if (navigator.geolocation) {
	navigator.geolocation.getCurrentPosition(success, error);
} else {
	error('Ваш браузер не поддерживает определение местонахождения');
}


function drawMap(lat,lon,zoom){
	if(!lat){
		lat = 55.742418;
		lon = 37.629268;
	}
	if(!zoom)
		zoom = 17;
	map = new GMap2(document.getElementById("map_canvas"));
	map.setMapType(G_HYBRID_MAP);
	map.addControl(new GLargeMapControl());

	map.setCenter(new GLatLng(lat, lon), zoom);
	
	GEvent.addListener(map,"click", function(overlay, latlng) {     
		if (latlng) { 
			alert(latlng)
		}
	});

}