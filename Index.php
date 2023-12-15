<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Chart JS dengan PHP</title>
    <style>
        /* CSS to style the title and name */
        .title-wrapper {
            text-align: center;
            margin: 16px 0;
        }

        .title {
            font-size: 36px;
            font-weight: bold;
        }

        .name {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }

        /* CSS to create a two-column layout for the charts */
        .charts-container {
            display: flex;
            justify-content: space-between;
        }

        .chart {
            flex: 1; /* Distribute available space equally between the charts */
            max-width: calc(50% - 16px); /* Calculate max-width with spacing */
        }
    </style>
</head>
<body>
    <br>
    <div class="outer-wrapper flex justify-center flex-wrap">
        <div class="title-wrapper">
            <h1 class="title">
                Dashboard Data Mahasiswa
            </h1>
            <div class="name">
                by Abdul Muffid - 3322600021 - 2 D4 SDT A
            </div>
        </div>
        <div class="dashboard w-[90%] md:w-full flex justify-center my-16">
            <div class="wrapper shadow-[0_8px_30px_rgb(0,0,0,0.12)] p-6 rounded-xl max-w-4xl w-full">
                <div class="charts-container">
                    <div class="chart">
                        <h2>Sebaran Mahasiswa Berdasarkan Jurusan</h2>
                        <canvas id="barChart"></canvas>
                    </div>
                    <div class="chart">
                        <h2>Sebaran Mahasiswa Berdasarkan Jenis Kelamin</h2>
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    // Koneksikan ke database
    $kon = mysqli_connect("127.0.0.1", "root", "", "akademik");
    //Inisialisasi nilai variabel awal
    $nama_jurusan = "";
    $jumlah_data_barchart = null;
    //Query SQL
    $sql_barchart = "select jurusan,COUNT(*) as 'total' from mahasiswa GROUP by jurusan";
    $hasil_barchart = mysqli_query($kon, $sql_barchart);
    while ($data = mysqli_fetch_array($hasil_barchart)) {
        //Mengambil nilai jurusan dari database
        $jur = $data['jurusan'];
        $nama_jurusan .= "'$jur'" . ", ";
        //Mengambil nilai total dari database
        $jum = $data['total'];
        $jumlah_data_barchart .= "$jum" . ", ";
    }
    $jenis_kelamin = "";
    $jumlah_data_piechart = null;
    $sql_piechart = "select jk,COUNT(*) as 'total' from mahasiswa GROUP by jk";
    $hasil_piechart = mysqli_query($kon, $sql_piechart);
    while ($data = mysqli_fetch_array($hasil_piechart)) {
        if ($data['jk'] == 1) {
            $jk = "Laki-laki";
        } else {
            $jk = "Perempuan";
        }
        $jenis_kelamin .= "'$jk'" . ", ";
        $jum = $data['total'];
        $jumlah_data_piechart .= "$jum" . ", ";
    }
    ?>
    <script>
        var ctx = document.getElementById('barChart').getContext('2d');
        var chart = new Chart(ctx, {
            // The type of chart we want to create
            type: 'bar',
            // The data for our dataset
            data: {
                labels: [<?php echo $nama_jurusan; ?>],
                datasets: [{
                    label: 'Data Jurusan Mahasiswa ',
                    backgroundColor: [
                        'rgb(23,107,135)',
                        'rgb(226,94,62)',
                        'rgb(192,130,97)',
                        'rgb(255,105,105)'
                    ],
                    borderColor: ['rgb(255, 99, 132)'],
                    data: [<?php echo $jumlah_data_barchart; ?>]
                }]
            },
            // Configuration options go here
            options: {
                responsive: true,
            }
        });
        var ctx = document.getElementById('pieChart').getContext('2d');
        var chart = new Chart(ctx, {
            // The type of chart we want to create
            type: 'pie',
            // The data for our dataset
            data: {
                labels: [<?php echo $jenis_kelamin; ?>],
                datasets: [{
                    label: 'Data Mahasiswa berdasarkan jenis kelamin',
                    backgroundColor: ['rgb(142,205,221)', 'rgb(247,140,162)'],
                    borderColor: ['rgb(0, 0, 0)'],
                    data: [<?php echo $jumlah_data_piechart; ?>]
                }]
            },
            // Configuration options go here
            options: {
                responsive: true,
                mainAspectRatio: true,
            }
        });
    </script>
</body>
</html>