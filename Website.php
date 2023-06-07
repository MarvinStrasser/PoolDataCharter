<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <title>Pool Data Charter</title>
    <style>
//        .chart-container {
//            width: 100%;
//            height: 26vh;
//            vertical-align: top;
//        }
        body {
            font-family: Verdana;
            font-size: 17px;
            text-align: center;
            h1-font-size: 50px;
        }
    </style>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha>


<meta http-equiv="refresh" content="60">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php
    // Set the database connection details
    $host = "localhost";
    $username = "admin";
    $password = "admin";
    $database = "measurements";

    $conn = mysqli_connect($host, $username, $password, $database);

    // Query the database for the latest entry
    $query = "SELECT * FROM data ORDER BY time DESC LIMIT 1";
    $result = mysqli_query($conn, $query);

    // Check if there is a result
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        // Print the latest entry data
        echo '<div class="container text-center">';
        echo "<h1>Latest Data</h1>";
        echo "<p>Timestamp: " . $row['time'] . "</p>";
        echo "<p>Temperature: " . $row['temp'] . "</p>";
        echo "<p>pH: " . $row['ph'] . "</p>";
        echo "<p>ORP: " . $row['orp'] . "</p>";
        echo '</div>';
    } else {
        echo "No entries found.";
    }
    //Close Connection
    mysqli_close($conn);
?>

    <div style="margin-top: 50px;"></div>
    <div class="chart-container" style="position: relative; height:40vh; width:100%">
        <canvas id="tempChart"></canvas>
    </div>

<?php
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
    }

    $lower = date_create("Now");
    date_add($lower, date_interval_create_from_date_string("-7 days"));
    $upper = date_create("Now");
    $dateStringStart = date_format($lower, "Y-m-d H:i:s");
    $dateStringEnd = date_format($upper, "Y-m-d H:i:s");

    $stmt = $conn->prepare('SELECT time, temp, ph, orp FROM data WHERE time >= ? AND time <= ?');
    $stmt->bind_param("ss", $dateStringStart, $dateStringEnd);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if there is a result
    if (mysqli_num_rows($result) > 0) {
      $data = array();
      foreach ($result as $row) {
         $data[] = $row;
      }
    }
    $result->close();
    $conn->close();

?>
<script>
        var datetime = [];
        var temp = [];
        var ph = [];
        var orp = [];
        var dataArray = <?php echo json_encode($data); ?>;
        for(var i in dataArray) {
                datetime.push(dataArray[i].time);
                temp.push(dataArray[i].temp);
                ph.push(dataArray[i].ph);
                orp.push(dataArray[i].orp);
        }




            var chartdataTemp = {
                labels: datetime,
                datasets: [{
                    label: "Temp",
                    fill: false,
                    lineTension: 0,
                    pointHoverBackgroundColor: "rgba(59, 89, 152, 1)",
                    pointHoverBorderColor: "rgba(59, 89, 152, 1)",
                    data: temp,
                    pointRadius: 0,
                    yAxisID: 'y',
                    xAxisID: 'x',
                    borderColor: 'cyan',
                    backgroundColor: 'cyan',
                }, {
                    label: "ORP",
                    fill: false,
                    lineTension: 0.1,
                    pointHoverBackgroundColor: "rgba(59, 89, 152, 1)",
                    pointHoverBorderColor: "rgba(59, 89, 152, 1)",
                    data: orp,
                    pointRadius: 0,
                    yAxisID: 'y1',
                    borderColor: 'darkkhaki',
                    backgroundColor: 'darkkhaki',

                 }, {
                    label: "pH",
                    fill: false,
                    lineTension: 0.1,
                    pointHoverBackgroundColor: "rgba(59, 89, 152, 1)",
                    pointHoverBorderColor: "rgba(59, 89, 152, 1)",
                    data: ph,
                    pointRadius: 0,
                    yAxisID: 'y2',
                    borderColor: 'indianred',
                    backgroundColor: 'indianred',

                }
                ],
            };




    var tempCtx = document.getElementById('tempChart').getContext('2d');
    var tempChart = new Chart(tempCtx, {
        type: 'line',
        data: chartdataTemp,
        options: {
            maintainAspectRatio: false,
            scales: {
                x: {
                   grid: {color: "#333333",},
                   },
                y: {
                   type: 'linear',
                   display: true,
                   grid: {color: "#333333",},



        title: {
          display: true,
          text: 'Temp (°C)',
          color: "cyan",
          padding: {top: 30, left: 0, right: 0, bottom: 0}
        },





                   position: 'left',
                   ticks: {
                      fontColor: "cyan",
                   },

               },
               y1: {
                   type: 'linear',
                   display: true,


        title: {
          display: true,
          text: 'ORP (mV)',
          color: "darkkhaki",
          padding: {top: 30, left: 0, right: 0, bottom: 0}
        },


                   position: 'right',
                   grid: {
                       drawOnChartArea: false,
                   },
               },
               y2: {
                   type: 'linear',
                   display: true,


        title: {
          display: true,
          text: 'pH',
          color: "indianred",
          padding: {top: 30, left: 0, right: 0, bottom: 0}
        },


                   position: 'right',
                   grid: {
                       drawOnChartArea: false,
                   },
               },

            }
        }
    });

</script>
</body>
</html>
