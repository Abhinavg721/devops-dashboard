<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
});

/*
|--------------------------------------------------------------------------
| CONTAINERS LIST
|--------------------------------------------------------------------------
*/
Route::get('/api/stats', function () {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, '/var/run/podman.sock');
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/v4.0.0/libpod/containers/json?all=true');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return response()->json(json_decode($response, true));
});

/*
|--------------------------------------------------------------------------
| START / STOP
|--------------------------------------------------------------------------
*/
Route::post('/api/start/{name}', function ($name) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, '/var/run/podman.sock');
    curl_setopt($ch, CURLOPT_URL, "http://localhost/v4.0.0/libpod/containers/$name/start");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    return curl_exec($ch);
});

Route::post('/api/stop/{name}', function ($name) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, '/var/run/podman.sock');
    curl_setopt($ch, CURLOPT_URL, "http://localhost/v4.0.0/libpod/containers/$name/stop");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    return curl_exec($ch);
});

/*
|--------------------------------------------------------------------------
| CONTAINER LOGS (REAL FIX 🔥)
|--------------------------------------------------------------------------
*/
Route::get('/api/logs/{name}', function ($name) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, '/var/run/podman.sock');
    curl_setopt($ch, CURLOPT_URL, "http://localhost/v4.0.0/libpod/containers/$name/logs?stdout=true&stderr=true&tail=100");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    return curl_exec($ch);
});

/*
|--------------------------------------------------------------------------
| HAPROXY STATS
|--------------------------------------------------------------------------
*/
Route::get('/api/haproxy/stats', function () {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://haproxy-lab:8404/;csv");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (!$response) {
        return response()->json(['error' => 'HAProxy not reachable']);
    }

    $lines = explode("\n", $response);
    $data = [];

    foreach ($lines as $line) {
        if (strpos($line, 'tomcat') !== false && strpos($line, 'BACKEND') === false) {

            $cols = explode(",", $line);

            if (count($cols) > 17) {
                $data[] = [
                    'name' => $cols[1],
                    'status' => $cols[17],
                    'requests' => $cols[7]
                ];
            }
        }
    }

    return response()->json($data);
});

/*
|--------------------------------------------------------------------------
| HAPROXY RELOAD
|--------------------------------------------------------------------------
*/
Route::post('/api/haproxy/reload', function () {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, '/var/run/podman.sock');
    curl_setopt($ch, CURLOPT_URL, "http://localhost/v4.0.0/libpod/containers/haproxy-lab/restart");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    return curl_exec($ch);
});

/*
| jenkins 

*/

Route::get('/api/jenkins-status', function () {

    $url = "http://jenkins:8080/job/devops-pipeline/api/json";

    $response = file_get_contents($url);

    if ($response === false) {
        return response()->json(['error' => 'Cannot connect to Jenkins']);
    }

    return response()->json(json_decode($response, true));
});


/* 
| jenkins logs 

*/

Route::get('/api/jenkins-logs', function () {

    $url = "http://jenkins:8080/job/devops-pipeline/lastBuild/consoleText";

    $logs = file_get_contents($url);

    if ($logs === false) {
        return response("Error fetching logs", 500);
    }

    return response($logs, 200)
        ->header('Content-Type', 'text/plain');
});
