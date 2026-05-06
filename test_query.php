<?php
require_once 'config/conn.php';

// Create connection
$conn = @oci_connect($SagUser, $SagPWD, $SagDB, 'AL32UTF8');

if (!$conn) {
    $e = oci_error();
    die("Database connection failed: " . $e['message']);
}

// SQL 1: Header
$sql_header = "
select t22.supplier
       ,t33.forecast_type,fc_year,week_no
       ,t33.first_day_of_week
from   
    (select * from z_bom_component t1
        where procure_type = 'F'
        and pur_grp between '101' and '110'
        and substr(t1.spec_procure,1,1) in ('2','3','4')
        ) t11,
     (select t02.*,decode(lifnr,'',lifnr_eord,lifnr) supplier  from z_eord_po t02) t22 ,
     (select t03.*
             ,substr(t03.forecast_date,1,4) fc_year,to_char(to_date(t03.forecast_date,'YYYYMMDD'), 'IW') as week_no
             ,case when t03.forecast_type = 'D' then t03.forecast_date 
             when t03.forecast_type = 'W' then to_char(trunc(to_date(t03.forecast_date,'YYYYMMDD'), 'IW'),'yyyymmdd') end  first_day_of_week
        from fc_cust_forecast t03
        where forecast_status = 'A' ) t33    
 where t11.part_no = t22.matnr
and t11.plant = t22.werks
and t11.plant = t33.plant
and t11.fg_part = t33.part_no
and t11.plant = '1201'
and t22.supplier = '0002110508'
and t33.forecast_date between '20250526' and to_char(add_months(to_date(t33.forecast_date,'YYYYMMDD'), 6) ,'YYYYMMDD')
and fg_part in ('000000000014411005')
and t33.first_day_of_week = '20250623'
group by t22.supplier
       ,t33.forecast_type,fc_year,week_no
       ,t33.first_day_of_week
order by t22.supplier
       ,t33.forecast_type,fc_year,week_no
       ,t33.first_day_of_week
";

// SQL 2: Data
$sql_data = "
select t11.plant,t11.part_no,t11.part_name,t11.model ,t22.supplier
       ,t33.forecast_type
       ,first_day_of_week
       ,sum(t33.forecast_qty*t11.fg_require_qty) consump_qty ,t11.unit
from  
    (select * from z_bom_component t01
        where procure_type = 'F'
        and pur_grp between '101' and '110'
        and substr(spec_procure,1,1) in ('2','3','4')
        ) t11,
     (select t02.*,decode(lifnr,'',lifnr_eord,lifnr) supplier  from z_eord_po t02) t22 ,
     (select t03.*
             ,substr(t03.forecast_date,1,4) fc_year,to_char(to_date(t03.forecast_date,'YYYYMMDD'), 'IW') as week_no
             ,case when t03.forecast_type = 'D' then t03.forecast_date 
             when t03.forecast_type = 'W' then to_char(trunc(to_date(t03.forecast_date,'YYYYMMDD'), 'IW'),'yyyymmdd') end  first_day_of_week
        from fc_cust_forecast t03
        where forecast_status = 'A' ) t33    
 where t11.part_no = t22.matnr
and t11.plant = t22.werks
and t11.plant = t33.plant
and t11.fg_part = t33.part_no
and t11.plant = '1201'
and t22.supplier = '0002110508'
and t33.forecast_date between '20250526' and to_char(add_months(to_date(t33.forecast_date,'YYYYMMDD'), 6) ,'YYYYMMDD')
--and fg_part in ('000000000014411005','000000000014411013','000000000014411030')
and fg_part in ('000000000014411005')
group by  t11.plant,t11.part_no,t11.part_name,t11.model ,t22.supplier
       ,t33.forecast_type
       ,first_day_of_week
       ,t11.unit
order by t11.part_no,t33.forecast_type,t33.first_day_of_week
";


function executeQuery($conn, $sql) {
    $stmt = oci_parse($conn, $sql);
    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        echo "<div style='color:red;'>Error: " . $e['message'] . "</div>";
        return false;
    }
    $results = [];
    while ($row = oci_fetch_array($stmt, OCI_ASSOC + OCI_RETURN_NULLS)) {
        $results[] = $row;
    }
    oci_free_statement($stmt);
    return $results;
}

$header_data = executeQuery($conn, $sql_header);
$body_data = executeQuery($conn, $sql_data);

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Test Page</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f7f6; }
        h2 { color: #333; border-left: 5px solid #007bff; padding-left: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #007bff; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f1f1; }
        .no-data { padding: 20px; text-align: center; color: #888; }
    </style>
</head>
<body>

    <h2>ส่วน Header (Query 1)</h2>
    <?php if ($header_data && count($header_data) > 0): ?>
    <table>
        <thead>
            <tr>
                <?php foreach (array_keys($header_data[0]) as $col): ?>
                    <th><?php echo htmlspecialchars($col); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($header_data as $row): ?>
            <tr>
                <?php foreach ($row as $val): ?>
                    <td><?php echo htmlspecialchars($val); ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="no-data">No data found for Header query.</div>
    <?php endif; ?>

    <h2>ส่วน Data (Query 2)</h2>
    <?php if ($body_data && count($body_data) > 0): ?>
    <table>
        <thead>
            <tr>
                <?php foreach (array_keys($body_data[0]) as $col): ?>
                    <th><?php echo htmlspecialchars($col); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($body_data as $row): ?>
            <tr>
                <?php foreach ($row as $val): ?>
                    <td><?php echo htmlspecialchars($val); ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="no-data">No data found for Data query.</div>
    <?php endif; ?>

</body>
</html>
