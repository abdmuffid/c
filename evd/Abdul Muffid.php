<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
    <title>Dashboard Peta Kendali</title>
    <style>
        body {
            margin: 0;
            background-color: #B6BBC4;
            color: #ffffff;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }

        h2 {
            text-align: center;
            background-color: #31304D;
            padding: 20px;
            border-radius: 10px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .chart-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            width: 100%;
        }

        .chart {
            width: 90%; /* Adjusted width to fit the screen */
            height: 40vh; /* Adjusted height to a percentage of the viewport height */
            max-width: 800px;
            border: 2px solid #ddd;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <h2>Abdul Muffid - 3322600021 - SDT 2022</h2>

    <?php
    // Function to send a notification to Telegram
    define('BOT_TOKEN', '6782860407:AAE2t0GwKfOVBfVuXXVhfUTVCnK1QTjPo7w');
    define('CHAT_ID', '1645578531');

    function kirimTelegram($pesan)
    {
        $pesan = urlencode($pesan);
        $API = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendmessage?chat_id=" . CHAT_ID . "&text=$pesan";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, $API);

        // Ignore SSL verification (not recommended for production)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Error handling
        if (!$result = curl_exec($ch)) {
            trigger_error(curl_error($ch));
        }

        curl_close($ch);

        return $result;
    }

    // Function to detect outliers
    function deteksiOutlier($ProporsiProdukCacat, $ucl, $lcl)
    {
        $outliers = [];

        foreach ($ProporsiProdukCacat as $index => $proporsi) {
            if ($proporsi > $ucl || $proporsi < $lcl) {
                $outliers[] = [
                    'Index' => $index,
                    'ProporsiProdukCacat' => $proporsi
                ];
            }
        }

        return $outliers;
    }
	
	// Function to remove data points identified as outliers
	function hapusDataOutlier($data, $outliers)
	{
		foreach ($outliers as $outlier) {
			// Hapus data outlier dari array data
			unset($data[$outlier['Index']]);
		}

		return $data;
	}

    // Koneksi ke database MySQL
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "evd";

    $conn = new mysqli($servername, $username, $password, $dbname);

    // Periksa koneksi
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Query untuk mengambil data dari tabel
    $sql = "SELECT Observasi, Banyaknya_Sampel, Banyaknya_Produk_Cacat, Proporsi_Produk_Cacat FROM data_perusahaan_kaos_tangan";
    $result = $conn->query($sql);

    // Data arrays untuk Plotly
    $data = [
        "x" => [],
        "defect_proportion" => [],
        "upper_limit" => [],
        "lower_limit" => [],
    ];

    $JumlahProdukCacat = [];
    $BanyakSampel = [];

    // Mengambil data dari hasil query
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data["x"][] = $row["Observasi"];

            // Menghitung proporsi produk cacat
            $defect_proportion = $row["Banyaknya_Produk_Cacat"] / $row["Banyaknya_Sampel"];
            $data["defect_proportion"][] = number_format($defect_proportion, 3); // Format to three decimal places

            // Mengumpulkan data untuk perhitungan CL
            $JumlahProdukCacat[] = $row["Banyaknya_Produk_Cacat"];
            $BanyakSampel[] = $row["Banyaknya_Sampel"];
        }

        // Menghitung jumlah produk cacat dan banyak sampel
        $totalJumlahProdukCacat = array_sum($JumlahProdukCacat);
        $totalBanyakSampel = array_sum($BanyakSampel);

        // Inisialisasi CL, UCL, dan LCL sesuai dengan nilai yang diberikan
        $cl = $totalJumlahProdukCacat / $totalBanyakSampel;
        $standev = sqrt($cl * (1 - $cl) / 200);
        $ucl = $cl + 3 * $standev;
        $lcl = $cl - 3 * $standev; // Nilai LCL yang baru

        $data["upper_limit"] = array_fill(0, count($data["x"]), number_format($ucl, 3));
        $data["lower_limit"] = array_fill(0, count($data["x"]), number_format($lcl, 3));
    }

    // Filter out data points outside UCL and LCL
    $filteredData = [
        "x" => [],
        "defect_proportion" => [],
        "upper_limit" => [],
        "lower_limit" => [],
    ];

    foreach ($data["x"] as $key => $observation) {
        $defectProportion = $data["defect_proportion"][$key];
        if ($defectProportion <= $ucl && $defectProportion >= $lcl) {
            $filteredData["x"][] = $observation;
            $filteredData["defect_proportion"][] = $defectProportion;
            $filteredData["upper_limit"][] = $data["upper_limit"][$key];
            $filteredData["lower_limit"][] = $data["lower_limit"][$key];
        }
    }

    // Menutup koneksi ke database
    $conn->close();

    // Detect outliers
    $outliers = deteksiOutlier($data["defect_proportion"], $ucl, $lcl);

    if (!empty($outliers)) {
        $notificationMessage = "Data Outlier Ditemukan:\n";
        foreach ($outliers as $outlier) {
            $notificationMessage .= "Data observasi ke-" . ($outlier['Index'] + 1) . ", Proporsi Produk Cacat: {$outlier['ProporsiProdukCacat']}\n";
        }

        // Kirim pesan ke Telegram
        kirimTelegram($notificationMessage);

        // Hapus data outlier
        $data = hapusDataOutlier($data, $outliers);

        // Kirim pesan ke Telegram bahwa data outlier sudah dihapus
        kirimTelegram("Data outlier sudah dihapus.");
    }
    ?>

    <div class="chart-container">
        <div class="chart" id="chart-with-outliers"></div>
        <div class="chart" id="chart-without-outliers"></div>
        <div class="chart" id="box-plot"></div>
    </div>

    <script>
        // Data from PHP
        var dataWithOutliers = [
            {
                x: <?php echo json_encode($data["x"]); ?>,
                y: <?php echo json_encode($data["defect_proportion"]); ?>,
                type: 'scatter',
                mode: 'lines+markers',
                name: 'Defect Proportion',
            },
            {
                x: <?php echo json_encode($data["x"]); ?>,
                y: <?php echo json_encode($data["upper_limit"]); ?>,
                type: 'scatter',
                mode: 'lines',
                name: 'Upper Limit',
                line: { color: 'red', dash: 'solid' },
            },
            {
                x: <?php echo json_encode($data["x"]); ?>,
                y: <?php echo json_encode($data["lower_limit"]); ?>,
                type: 'scatter',
                mode: 'lines',
                name: 'Lower Limit',
                line: { color: 'green', dash: 'solid' },
            },
            {
                x: <?php echo json_encode($data["x"]); ?>,
                y: <?php echo json_encode(array_fill(0, count($data["x"]), number_format($cl, 3))); ?>,
                type: 'scatter',
                mode: 'lines',
                name: 'Center Line',
                line: { color: 'yellow', dash: 'solid' },
            },
        ];

        // Layout configuration
        var layoutWithOutliers = {
            title: {
                text: 'Data with Outliers',
                font: {
                    size: 16
                }
            },
            xaxis: {
                title: 'Observation Points',
                tickmode: 'array',
                tickvals: <?php echo json_encode($data["x"]); ?>,
            },
            yaxis: {
                title: 'Defect Proportion',
            },
            margin: {
                l: 75,
                r: 75,
                b: 75,
                t: 75,
                pad: 10
            },
            paper_bgcolor: '#f9f9f9',
        };

        // Plot the chart with outliers
        Plotly.newPlot('chart-with-outliers', dataWithOutliers, layoutWithOutliers);

        // Data for the filtered chart without outliers
        var dataWithoutOutliers = [
            {
                x: <?php echo json_encode($filteredData["x"]); ?>,
                y: <?php echo json_encode($filteredData["defect_proportion"]); ?>,
                type: 'scatter',
                mode: 'lines+markers',
                name: 'Defect Proportion',
            },
            {
                x: <?php echo json_encode($filteredData["x"]); ?>,
                y: <?php echo json_encode($filteredData["upper_limit"]); ?>,
                type: 'scatter',
                mode: 'lines',
                name: 'Upper Limit',
                line: { color: 'red', dash: 'solid' },
            },
            {
                x: <?php echo json_encode($filteredData["x"]); ?>,
                y: <?php echo json_encode($filteredData["lower_limit"]); ?>,
                type: 'scatter',
                mode: 'lines',
                name: 'Lower Limit',
                line: { color: 'green', dash: 'solid' },
            },
            {
                x: <?php echo json_encode($filteredData["x"]); ?>,
                y: <?php echo json_encode(array_fill(0, count($filteredData["x"]), number_format($cl, 3))); ?>,
                type: 'scatter',
                mode: 'lines',
                name: 'Center Line',
                line: { color: 'yellow', dash: 'solid' },
            },
        ];

        // Layout configuration for the filtered chart without outliers
        var layoutWithoutOutliers = {
            title: {
                text: 'Data without Outliers',
                font: {
                    size: 16
                }
            },
            xaxis: {
                title: 'Observation Points',
                tickmode: 'array',
                tickvals: <?php echo json_encode(array_slice($filteredData["x"], 1)); ?>, // Exclude observation number 9
                ticktext: <?php echo json_encode(array_slice($filteredData["x"], 1)); ?>,
            },
            yaxis: {
                title: 'Defect Proportion',
            },
            margin: {
                l: 75,
                r: 75,
                b: 75,
                t: 75,
                pad: 10
            },
            paper_bgcolor: '#f9f9f9',
        };

        // Plot the chart without outliers
        Plotly.newPlot('chart-without-outliers', dataWithoutOutliers, layoutWithoutOutliers);

        // Data for boxplot
        var dataBoxPlot = [
            {
                y: <?php echo json_encode($data["defect_proportion"]); ?>,
                type: 'box',
                name: 'Defect Proportion',
                marker: { color: 'blue' } // Ubah warna box plot
            }
        ];

        // Layout configuration for the box plot
        var layoutBoxPlot = {
            title: {
                text: 'Box Plot of Defect Proportion',
                font: {
                    size: 16
                }
            },
            yaxis: {
                title: 'Defect Proportion',
            },
            margin: {
                l: 75,
                r: 75,
                b: 75,
                t: 75,
                pad: 10
            },
            paper_bgcolor: '#f9f9f9',
        };

        // Plot the box plot
        Plotly.newPlot('box-plot', dataBoxPlot, layoutBoxPlot);
    </script>

</body>

</html>