var map;

var MARKER_TYPE_IMHERE = 1;
var MARKER_TYPE_STATION = 2;

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
	drawMap(lat, lon, 14)
	addMarker(MARKER_TYPE_IMHERE, lat, lon);
}


function addMarker(type,lat,lon,title){
	var icon = new GIcon(G_DEFAULT_ICON);
	
	if(type == MARKER_TYPE_IMHERE)
		icon.image = "/static/default/img/markers/imhere.png";
	if(type == MARKER_TYPE_STATION)
		icon.image = "/static/default/img/markers/station.png";
	
	markerOptions = {
		icon:icon,
		title : title
	};
	var point = new GLatLng(lat,lon);
	var marker = new GMarker(point, markerOptions);
	map.addOverlay(marker);
	
	var html = "станция метро <b>" + title + "</b>";
	GEvent.addListener(marker, 'click', function() {
		map.setCenter(new GLatLng(lat, lon));
		marker.openInfoWindowHtml(html);
	});
}

function error(msg) {
	var s = document.querySelector('#status');
	s.innerHTML = typeof msg == 'string' ? msg : "failed";
	s.className = 'fail';
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
		zoom = 11;
	map = new GMap2(document.getElementById("map_canvas"));
	map.setMapType(G_SATELLITE_MAP);
	map.addControl(new GLargeMapControl());

	map.setCenter(new GLatLng(lat, lon), zoom);
	
	GEvent.addListener(map,"click", function(overlay, latlng) {     
		if (latlng) { 
			//alert(latlng)
		}
	});
	if(markers)
		for(var i in markers){
			addMarker(MARKER_TYPE_STATION, markers[i].lat,markers[i].lon,markers[i].title);
		}
}