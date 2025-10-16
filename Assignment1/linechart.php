<!DOCTYPE HTML>
<html>
<head>
  <meta charset="UTF-8">
  <title>Light & Power Level Chart</title>
  <script src="https://canvasjs.com/assets/script/jquery-1.11.1.min.js"></script>
  <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>

  <script>
    
    window.onload = function () {
      var lightData = [];
      var powerData = [];

      var chart = new CanvasJS.Chart("chartContainer", {
        title: {
          text: "Light & Power Levels Over Time"
        },
        axisX: {
          title: "Time",
          valueFormatString: "DD-MM-YY HH:mm:ss"
        },
        axisY: {
          title: "Sensor Values",
          includeZero: false
        },
        toolTip: {
          shared: true
        },
        legend: {
          cursor: "pointer",
          verticalAlign: "top",
          horizontalAlign: "center",
          dockInsidePlotArea: true,
          itemclick: function (e) {
            e.dataSeries.visible = !(typeof e.dataSeries.visible === "undefined" || e.dataSeries.visible);
            chart.render();
          }
        },
        data: [
          {
            type: "line",
            name: "Light Level",
            showInLegend: true,
            markerSize: 0,
            xValueType: "dateTime",
            dataPoints: lightData
          },
          {
            type: "line",
            name: "Power Level",
            showInLegend: true,
            markerSize: 0,
            xValueType: "dateTime",
            dataPoints: powerData
          }
        ]
      });
      var latestTimestamp = 0;
      function addData(data) {
  for (var i = 0; i < data.record.length; i++) {
    var currentValues = data.record[i];

    if (!currentValues.timestamp) continue;

    var ts = parseFloat(currentValues.timestamp);
    var dateObj = new Date(ts * 1000);

    // Only add if it's newer than our last known timestamp
    if (ts > latestTimestamp) {
      lightData.push({
        x: dateObj,
        y: parseFloat(currentValues.light_level)
      });

      powerData.push({
        x: dateObj,
        y: parseFloat(currentValues.power_level)
      });

      if (ts > latestTimestamp) {
        latestTimestamp = ts;
      }
    }
  }

  chart.render();
}


      // Function to get new data from the server
      function updateData() {
        $.getJSON("http://iotserver.com/canvasjs3.6/datafortuts/convertXMLtoJSON.php", addData); 
      }

      // Initial call to load data
      updateData();
      
      // Refresh every 5 seconds
      setInterval(updateData, 5000);
    };
  </script>
</head>
<body>
  <div id="chartContainer" style="height: 370px; width: 100%; margin: 0px auto;"></div>
</body>
</html>

<?php

$xmlFile = "data.xml";

if (!file_exists($xmlFile)) {
    echo "<p>data.xml not found.</p>";
    exit;
}

$xml = simplexml_load_file($xmlFile);
$totalRecords = 0;
$totalCollisions = 0;
$totalPowerIssues = 0;
$currentThreshold = "-";

echo "<h2>📋 Log Summary</h2>";
echo "<table style='border: 1px solid black; border-collapse: collapse;'>";
echo "<tr>
        <th style='border: 1px solid black; padding: 8px;'>Device Timestamp</th>
        <th style='border: 1px solid black; padding: 8px;'>Light Level</th>
        <th style='border: 1px solid black; padding: 8px;'>Power Level</th>
        <th style='border: 1px solid black; padding: 8px;'>Power Status</th>
        <th style='border: 1px solid black; padding: 8px;'>Collision</th>
        <th style='border: 1px solid black; padding: 8px;'>Threshold</th>
      </tr>";

foreach ($xml->record as $record) {
    $timestamp = isset($record->timestamp) ? date("Y-m-d H:i:s", (float)$record->timestamp) : "N/A";
    $light = isset($record->light_level) ? (float)$record->light_level : (isset($record->humidity) ? (float)$record->humidity : 0);
    $power = isset($record->power_level) ? (float)$record->power_level : (isset($record->temperature) ? (float)$record->temperature : 0);
    $coll  = isset($record->coll_state) ? (int)$record->coll_state : (isset($record->collision) ? (int)$record->collision : 0);
    $thres = isset($record->light_threshold) ? (float)$record->light_threshold : (isset($record->threshold) ? (float)$record->threshold : "-");

    $totalRecords++;
    if ($coll === 1) $totalCollisions++;
    if ($power < 0 || $power > 100) $totalPowerIssues++;
    $currentThreshold = $thres;

    $powerStatus = "Normal";
    if ($power < 0) $powerStatus = "Brownout";
    elseif ($power > 100) $powerStatus = "Surge";

    echo "<tr>
            <td style='border: 1px solid black; padding: 8px;'>$timestamp</td>
            <td style='border: 1px solid black; padding: 8px;'>$light</td>
            <td style='border: 1px solid black; padding: 8px;'>$power</td>
            <td style='border: 1px solid black; padding: 8px;'>$powerStatus</td>
            <td style='border: 1px solid black; padding: 8px;'>" . ($coll ? "Yes" : "No") . "</td>
            <td style='border: 1px solid black; padding: 8px;'>$thres</td>
          </tr>";
}
echo "</table>";

echo "<p><strong>Total Records:</strong> $totalRecords</p>";
echo "<p><strong>Power Issues:</strong> $totalPowerIssues</p>";
echo "<p><strong>Collisions:</strong> $totalCollisions</p>";
echo "<p><strong>Latest Threshold:</strong> $currentThreshold</p>";
?>
