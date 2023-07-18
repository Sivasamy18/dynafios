<html lang=en>
  <head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
  </head>
  <body>
    <div id="barchart_div_{{$data->contract_id}}_{{$data->period_number}}"></div>
  </body>
  <script type="text/javascript">
    google.charts.load('current', {packages: ['corechart', 'bar']});
    google.charts.setOnLoadCallback(drawStacked);

    function drawStacked() {
      var data3 = google.visualization.arrayToDataTable([
        [
          'Blank',
          'Compensation',
          {role: 'style'},
          {type: 'string',role: 'tooltip'},
          {type: 'number',role: 'id'},
          '90',
          {type: 'boolean',role: 'certainty'},
          {type: 'string',role: 'tooltip'},
          '75',
          {type: 'boolean',role: 'certainty'},
          {type: 'string',role: 'tooltip'},
          '50',
          {type: 'boolean',role: 'certainty'},
          {type: 'string',role: 'tooltip'},
          'Actual',
          {type: 'boolean',role: 'certainty'},
          {type: 'string',role: 'tooltip'},
          {type: 'string',role: 'annotation'},
        ],

        [
          '',
          null,
          '',
          '',
          2,
          {{$data->comp_per_period_90}},
          false,
          '90th Percentile\n{{$data->comp_string_per_period_90}}',
          {{$data->comp_per_period_75}},
          false,
          '75th Percentile\n{{$data->comp_string_per_period_75}}',
          {{$data->comp_per_period_50}},
          false,
          '50th Percentile\n{{$data->comp_string_per_period_50}}',
          {{$data->actual_comp}},
          true,
          'Actual\n{{$data->actual_comp_string}}',
          '{{$data->actual_comp_string}}'
        ],

        [
          '',
          {{$data->comp_per_period}},
          '{{$data->bar_style}}',
          @if($data->is_wrvu_payment)
          'Compensation\n{{$data->comp_string_per_period}}\n( wRVU Gap: {{$data->wrvu_gap}})\n(Compensation Gap: {{$data->comp_gap_string}})',
          @else
          'Compensation\n{{$data->comp_string_per_period}}\n( wRVU Gap: {{$data->wrvu_gap}})',
          @endif
          2,
          {{$data->comp_per_period_90}},
          false,
          '90th Percentile\n{{$data->comp_string_per_period_90}}\n{{$data->wrvu_per_period_90}} wRVU',
          {{$data->comp_per_period_75}},
          false,
          '75th Percentile\n{{$data->comp_string_per_period_75}}\n{{$data->wrvu_per_period_75}} wRVU',
          {{$data->comp_per_period_50}},
          false,
          '50th Percentile\n{{$data->comp_string_per_period_50}}\n{{$data->wrvu_per_period_50}} wRVU',
          {{$data->actual_comp}},
          true,
          'Actual\n{{$data->actual_comp_string}}\n{{$data->duration}} wRVU',
          ''
        ],

        [
          '',
          null,
          '',
          '',
          null,
          {{$data->comp_per_period_90}},
          false,
          '90th Percentile\n{{$data->wrvu_per_period_90}} wRVU',
          {{$data->comp_per_period_75}},
          false,
          '75th Percentile\n{{$data->wrvu_per_period_75}} wRVU',
          {{$data->comp_per_period_50}},
          false,
          '50th Percentile\n{{$data->wrvu_per_period_50}} wRVU',
          {{$data->actual_comp}},
          true,
          'Actual\n{{$data->duration}} wRVU',
          '{{$data->duration}} wRVU'
        ]

        ]);

      var options3 = {
        title: "{{$data->period}}",
        isStacked: true,
        chartArea: {
          left: 10,
          top: "10%",
          bottom: 40,
          width: "90%",
          height: "90%"
        },
        height: 250,
        annotations: {
          style: 'line'
        },
        seriesType: 'bars',
        series: {
          0: {
            type: 'bar',
            enableInteractivity: true
          },
          1: {
            type: 'line',
            color: 'black',
            enableInteractivity: true
          },
          2: {
            type: 'line',
            color: 'black',
            enableInteractivity: true
          },
          3: {
            type: 'line',
            color: 'black',
            enableInteractivity: true
          },
          4: {
            type: 'line',
            color: 'black',
            enableInteractivity: true
          }
        },
        legend: {
          position: "none"
        },
        hAxis: {
        	ticks: [{v:{{$data->comp_per_period_50}}, f:'50th\nPercentile'},{v:{{$data->comp_per_period_75}},f:'75th\nPercentile'},{v:{{$data->comp_per_period_90}},f:'90th\nPercentile'}]
        },
        backgroundColor: '#fff'
      };



      var chart3 = new google.visualization.BarChart(document.getElementById('barchart_div_{{$data->contract_id}}_{{$data->period_number}}'));

      chart3.draw(data3, options3);
    }

  </script>
</html>
