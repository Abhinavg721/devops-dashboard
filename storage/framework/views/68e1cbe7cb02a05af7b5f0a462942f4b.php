<!DOCTYPE html>
<html>
<head>
    <title>DevOps Dashboard</title>

    <style>
        body {
            background: #0f172a;
            color: white;
            font-family: Arial;
        }

        h1 {
            text-align: center;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
            padding: 20px;
        }

        .card {
            background: #1e293b;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .running { color: #22c55e; }
        .stopped { color: #ef4444; }

        button {
            margin: 5px;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .start { background: green; color: white; }
        .stop { background: red; color: white; }

        /* 🔥 MODAL STYLE */
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
        }

        .modal-content {
            background: #020617;
            margin: 5% auto;
            padding: 20px;
            width: 80%;
            border-radius: 10px;
        }

        .close {
            float: right;
            font-size: 22px;
            cursor: pointer;
            color: red;
        }

        .logs {
            background: black;
            color: lime;
            padding: 10px;
            height: 400px;
            overflow: auto;
        }
    </style>

    <script>

        async function loadContainers() {
            const res = await fetch('/api/stats');
            const data = await res.json();

            let html = "";

            data.forEach(c => {
                let name = c.Names[0].replace('/', '');
                let status = c.State === "running" ? "running" : "stopped";

                html += `
                    <div class="card">
                        <h3>${name}</h3>
                        <p class="${status}">${status}</p>

                        <button class="start" onclick="startContainer('${name}')">Start</button>
                        <button class="stop" onclick="stopContainer('${name}')">Stop</button>
                        <button onclick="openLogs('${name}')">Logs</button>
                    </div>
                `;
            });

            document.getElementById("containers").innerHTML = html;
        }

        async function startContainer(name) {
            await fetch(`/api/start/${name}`, { method: 'POST' });
            loadContainers();
        }

        async function stopContainer(name) {
            await fetch(`/api/stop/${name}`, { method: 'POST' });
            loadContainers();
        }

        /* 🔥 OPEN LOG MODAL */
        async function openLogs(name) {
            document.getElementById("logModal").style.display = "block";
            document.getElementById("logTitle").innerText = name + " Logs";

            const res = await fetch(`/api/logs/${name}`);
            const text = await res.text();

            document.getElementById("logContent").innerText = text;
        }

        /* 🔥 CLOSE MODAL */
        function closeLogs() {
            document.getElementById("logModal").style.display = "none";
        }

        /* 🔥 HAPROXY */
        async function loadHAProxy() {
            const res = await fetch('/api/haproxy/stats');
            const data = await res.json();

            let html = "";

            data.forEach(c => {
                let color = c.status === "UP" ? "#22c55e" : "#ef4444";

                html += `
                    <div class="card">
                        <h3>${c.name}</h3>
                        <p style="color:${color}">${c.status}</p>
                        <p>Requests: ${c.requests}</p>
                    </div>
                `;
            });

            document.getElementById("haproxy").innerHTML = html;
        }

        async function reloadHAProxy() {
            await fetch('/api/haproxy/reload', { method: 'POST' });
            alert("HAProxy Reloaded");
        }

        setInterval(() => {
            loadContainers();
            loadHAProxy();
        }, 4000);

        window.onload = () => {
            loadContainers();
            loadHAProxy();
        };

    </script>
</head>

<body>

<h1>🚀 DevOps Control Dashboard</h1>

<h2 style="text-align:center;">📦 Containers</h2>
<div class="grid" id="containers"></div>

<h2 style="text-align:center;">⚖️ HAProxy</h2>
<div style="text-align:center;">
    <button onclick="reloadHAProxy()">Reload HAProxy</button>
</div>
<div class="grid" id="haproxy"></div>

<!-- 🔥 LOG MODAL -->
<div id="logModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeLogs()">✖</span>
        <h2 id="logTitle"></h2>
        <pre id="logContent" class="logs"></pre>
    </div>
</div>

</body>
</html>


<h2 style="margin-top:30px;">⚙️ Jenkins Pipeline Status</h2>

<table border="1" style="width:100%; text-align:center;">
    <thead>
        <tr>
            <th>Job</th>
            <th>Status</th>
            <th>Last Build</th>
        </tr>
    </thead>
    <tbody id="jenkins-status"></tbody>
</table>



<script>
async function loadJenkinsStatus() {
    try {
        const res = await fetch('/api/jenkins-status');
        const data = await res.json();

        let status = data.color;

        if (status.includes("blue")) {
            status = "🟢 SUCCESS";
        } else if (status.includes("red")) {
            status = "🔴 FAILED";
        } else if (status.includes("yellow")) {
            status = "🟡 UNSTABLE";
        } else if (status.includes("anime")) {
            status = "🔄 RUNNING";
        } else {
            status = "⚪ UNKNOWN";
        }

        const html = `
            <tr>
                <td>${data.name}</td>
                <td>${status}</td>
                <td>#${data.lastBuild?.number || '-'}</td>
            </tr>
        `;

        document.getElementById("jenkins-status").innerHTML = html;

    } catch (e) {
        document.getElementById("jenkins-status").innerHTML =
            "<tr><td colspan='3'>❌ Jenkins not reachable</td></tr>";
    }
}

// refresh every 5 sec
setInterval(loadJenkinsStatus, 5000);
</script>



<button onclick="showJenkinsLogs()" style="
    background:#ff9800;
    color:white;
    padding:10px;
    border:none;
    border-radius:8px;
    margin-top:10px;
">
📜 View Jenkins Logs
</button>

<div id="jenkinsLogsModal" style="
    display:none;
    position:fixed;
    top:10%;
    left:10%;
    width:80%;
    height:70%;
    background:black;
    color:#00ff00;
    padding:20px;
    overflow:auto;
    border-radius:10px;
    z-index:9999;
">
    <button onclick="closeLogs()" style="
        float:right;
        background:red;
        color:white;
        border:none;
        padding:5px 10px;
    ">Close</button>

    <pre id="jenkinsLogsContent"></pre>
</div>



<script>
async function showJenkinsLogs() {
    const res = await fetch('/api/jenkins-logs');
    const data = await res.text();

    document.getElementById('jenkinsLogsContent').innerText = data;
    document.getElementById('jenkinsLogsModal').style.display = 'block';
}

function closeLogs() {
    document.getElementById('jenkinsLogsModal').style.display = 'none';
}
</script>


<?php /**PATH /var/www/html/resources/views/dashboard.blade.php ENDPATH**/ ?>