<html>

<head>
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


    <!-- Import all your used libraries here -->
    <script src='https://api.mapbox.com/mapbox-gl-js/v1.8.0/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v1.8.0/mapbox-gl.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css" integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js" integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew==" crossorigin=""></script>

<body style="font-family: Arial,Helvetica Neue,Helvetica,sans-serif; ">
    <center>
        <img src="logo.jpg" style="height:128px;width:128px;">
    </center>
    <!--<iframe width="100%" height="100%" style="position:absolute;border:none;top:0;left:0;right:0;bottom:0;" scroll="no" onload="resizeIframe(this)" src="https://script.google.com/macros/s/AKfycbyaY60T7YR6mw4ZgB1gXuba7QyL2-21fNeIkIHgxVlz3ZKol_N2/exec" sandbox="allow-scripts allow-pointer-lock allow-same-origin allow-forms allow-modals allow-popups" ></iframe>-->

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
        function addMarker(x, y, r, tsunami) {
            if (tsunami == 1){
                te = " + tsunami"
            }
            L.circleMarker([x, y], {
                color: "red",
                radius: r * 5
            }).addTo(map)
                .bindPopup('Earthquake' + te)
                .openPopup();
            te = "";
        }
        var info = document.getElementById("dom-target").innerText.split("}");

        var store = [];

        info.forEach(el => {
            store.push(el.replace(/ /g, '').replace(/\n/g, ''));
        });

        store.forEach(el => {
            addMarker(el.split(",")[1], el.split(",")[0], el.split(",")[2]);
        });
    </script>
</body>

</html>