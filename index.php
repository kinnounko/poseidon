<html>

<head>
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <script src='https://api.mapbox.com/mapbox-gl-js/v1.8.0/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v1.8.0/mapbox-gl.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css" integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js" integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew==" crossorigin=""></script>

    <div id="dom-target" style="display: none;">
        <?php
        $output = "";

        ini_set("allow_url_fopen", 1);
        $json = file_get_contents('https://earthquake.usgs.gov/earthquakes/feed/v1.0/summary/all_month.geojson');
        $object = json_decode($json);
        for ($i = 0; $i < 20000; $i++) {
            $test = $object->features[$i]->geometry->coordinates;
            if (strpos($object->features[$i]->properties->place, "Indone") != false) {
                $output .= $test[0] . "," . $test[1] . "," . $object->features[$i]->properties->mag . "," . $object->features[$i]->properties->tsunami . "}";
            }
        }
        echo htmlspecialchars($output);
        ?>
    </div>

<body style="font-family: Arial,Helvetica Neue,Helvetica,sans-serif; ">
    <img src="logo.jpg" style="height:128px;width:128px;">
    <div id="leafletmap"></div>

    <style>
        #leafletmap {
            height: 500px;
        }
    </style>
    <script>
        // initialize the map
        var map = L.map('leafletmap').setView([-0.8917, 119.8707], 5);

        // TODO: replace reference and data + origin of map
        // load a tile layer
        L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
                attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, ' +
                    '<a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
                    'Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
                maxZoom: 18,
                id: 'mapbox/streets-v11',
                tileSize: 512,
                zoomOffset: -1,
                accessToken: 'pk.eyJ1IjoibXAyNiIsImEiOiJjaXBsbHBvbTAwMDhtdmJudGU2cHBjZTN3In0._OyAEEAuscIVkPmPuCF5pg'
            })
            .addTo(map);
        var te = "";
        var tese = 0;

        var greenIcon = new L.Icon({
            iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        function addCircleMarker(x, y, r, tsunami) {
            var coll = "";
            if (tsunami == 1) {
                te = " + tsunami"
            } else if (r < 4.3) {
                coll = "yellow";
            } else if (r > 5) {
                coll = "red";
            } else {
                coll = "orange";
            }
            L.circleMarker([x, y], {
                    color: coll,
                    radius: 50 - ((6 - r) * 20),
                    fillOpacity: 1.0
                }).addTo(map)
                .bindPopup('Earthquake' + te + ", mag. " + r)
                .openPopup();
            te = "";

        }

        function addMarker(x, y, message) {
            L.marker([x, y]).addTo(map)
                .bindPopup('Shelter: ' + message)
                .openPopup();
        }


        addMarker(-7.585, 108.648, "place for 2 cows and a car");
        addMarker(-0.971290, 110.698114, "we have lorem ipsum and place for a sit amet");
        addMarker(-1.059157, 115.930861, "place for a cow and a 2 people");
        addMarker(-1.857497, 119.724974, "food and place for 3 people");
        addMarker(-3.168490, 121.654228, "place for tools and lorem ipsum dolor sit amet");

        var info = document.getElementById("dom-target").innerText.split("}");

        var store = [];

        info.forEach(el => {
            store.push(el.replace(/ /g, '').replace(/\n/g, ''));
        });

        store.forEach(el => {
            addCircleMarker(el.split(",")[1], el.split(",")[0], el.split(",")[2], el.split(",")[3]);
        });
    </script>
</body>

</html>