<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <meta name="google-signin-client_id" content="271875377141-punjp2v0agij256gvscbd1m4or6ptqt4.apps.googleusercontent.com">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.9.1/underscore-min.js"></script>
    <script src='https://api.mapbox.com/mapbox-gl-js/v1.8.0/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v1.8.0/mapbox-gl.css' rel='stylesheet' />

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css" integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js" integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew==" crossorigin=""></script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" integrity="sha512-Rksm5RenBEKSKFjgI3a41vrjkw4EVPlJ3+OiI65vTjIdo9brlAacEuKOiQ5OFh7cOI1bkDwLqdLw3Zg0cRJAAQ==" crossorigin="" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.3.0/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.3.0/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" integrity="sha512-/Nsx9X4HebavoBvEBuyp3I7od5tA0UzAxs+j83KgC8PU0kgB4XiK4Lfe4y4cgBtaRJQEIFCW+oC506aPT2L1zw==" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.3.0/dist/leaflet.markercluster.js"></script>

    <script src="leaflet_canvas_layer.js"></script>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">
</head>

<body style="font-family: Arial,Helvetica Neue,Helvetica,sans-serif; ">


    <style>
        #leafletmap {
            height: 500px;
            float: left;
            margin-left: 5%;
        }

        body {
            background-color: #7f5a83;
            background-image: linear-gradient(315deg, #7f5a83 0%, #0d324d 74%);
            height: 100%;
        }

        html {
            min-height: 100%;
        }

        .noselect {
            user-select: none;
            -moz-user-select: none;
            -khtml-user-select: none;
            -webkit-user-select: none;
            -o-user-select: none;
        }

        .twitter-container {
            width: 20%;
            float: right;
            margin-right: 10%;
            overflow: auto;
            height: 60%;
        }

        .g-signin2 {
            float: right;
        }
    </style>
    <table class="noselect" style="height: 28px; width: 261px;">
        <tbody>
            <tr>
                <td style="width: 93px;"><img style="width: 100px; height: 100px;" src="http://pseidon.ml/logo.png" /></td>
                <td style="width: 152px;font-family: 'Open Sans', sans-serif; color:aliceblue; font-size:30pt; vertical-align:middle"><b>pseidon</b></td>
            </tr>
        </tbody>
    </table>
    <div><div class="g-signin2" data-onsuccess="onSignIn"></div> <img id="userimage"></div>


    <div>
        <div id="leafletmap" style="height:60%;width:60%;padding-left:20px"></div>

        <span class="twitter-container">
            <a class="twitter-timeline" href="https://twitter.com/WHO?ref_src=twsrc%5Etfw">Tweets by WHO</a>
            <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
        </span>
    </div>

    <div style="display: none" id="deaths">
        <?php
        ini_set("allow_url_fopen", 1);
        $json = file_get_contents('https://coronavirus-tracker-api.herokuapp.com/deaths');
        $object = json_decode($json);
        $output = "";

        for ($i = 0; $i < 10000; $i++) {
            $deaths = $object->locations[$i];
            if ($deaths->latest != 0) {
                $output .= $deaths->coordinates->lat . "," . $deaths->coordinates->long . "," . $deaths->latest . "}";
            }
        }
        echo htmlspecialchars($output);
        ?>
    </div>

    <div id="confirmed" style="display: none">
        <?php
        ini_set("allow_url_fopen", 1);
        $json = file_get_contents('https://coronavirus-tracker-api.herokuapp.com/confirmed');
        $object = json_decode($json);
        $output = "";

        for ($i = 0; $i < 10000; $i++) {
            $confirmed = $object->locations[$i];
            if ($confirmed->latest != 0) {
                $output .= $confirmed->coordinates->lat . "," . $confirmed->coordinates->long . "," . $confirmed->latest . "}";
            }
        }
        echo htmlspecialchars($output);
        ?>
    </div>

    <script>
        function onSignIn(googleUser) {
            var profile = googleUser.getBasicProfile();
            console.log('ID: ' + profile.getId()); // Do not send to your backend! Use an ID token instead.
            console.log('Name: ' + profile.getName());
            document.getElementById("userimage").src = profile.getImageUrl();
            console.log('Email: ' + profile.getEmail()); // This is null if the 'email' scope is not present.
        }

        function arrUnique(arr) {
            var cleaned = [];
            arr.forEach(function(itm) {
                var unique = true;
                cleaned.forEach(function(itm2) {
                    if (_.isEqual(itm, itm2)) unique = false;
                });
                if (unique) cleaned.push(itm);
            });
            return cleaned;
        }

        var confirmedIcon = L.icon({
            iconUrl: 'confirmed.png',
            iconSize: [32, 37],
            iconAnchor: [16, 37],
            popupAnchor: [0, -28]
        });

        var deathIcon = L.icon({
            iconUrl: 'death.png',
            iconSize: [32, 37],
            iconAnchor: [16, 37],
            popupAnchor: [0, -28]
        });

        function addCircleMarker(x, y, death, n) {
            var eventName = n + " confirmed cases";
            var radius = 10;
            var iconb = confirmedIcon;
            if (death == 1) {
                eventName = n + " deaths";
                iconb = deathIcon;
            }

            L.marker([x, y], {
                    icon: iconb
                }).addTo(map)
                .bindPopup(eventName)
                .openPopup();
        }

        function processData(dataarray, status) {
            var store = [];

            dataarray.forEach(el => {
                store.push(el.replace(/ /g, '').replace(/\n/g, ''));
            });
            store.pop();


            store = arrUnique(store);

            store.forEach(el => {
                addCircleMarker(el.split(",")[0], el.split(",")[1], status, el.split(",")[2]);
            });
        }
        // initialize the map
        var map = L.map('leafletmap').setView([0, 0], 2);

        // TODO: replace reference and data + origin of map
        // load a tile layer
        L.tileLayer("http://{s}.sm.mapstack.stamen.com/(toner-lite,$fff[difference],$fff[@23],$fff[hsl-saturation@20])/{z}/{x}/{y}.png")
            .addTo(map);

        processData(document.getElementById("confirmed").innerText.split("}"), 0);
        processData(document.getElementById("deaths").innerText.split("}"), 1);



        //addMarkers(document.getElementById("confirmed").innerText.split("}"), 0)
    </script>
</body>

</html>