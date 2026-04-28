<?php
echo "<h2>SAP ERP Connection Test</h2>";

// 1. Check OCI8
echo "<b>1. OCI8 Extension:</b> ";
if (!function_exists('oci_connect')) {
    echo "<span style='color:red'>❌ NOT INSTALLED</span><br>";
    exit;
}
echo "<span style='color:green'>✅ OK</span><br>";

// 2. Try connect
require_once '../config/erp_db.php';
echo "<b>2. Connecting to ECCERP (DSP user)...</b> ";

$conn = @oci_connect("DSP", "dsp", "ECCERP", "WE8DEC");
if (!$conn) {
    $e = oci_error();
    echo "<span style='color:red'>❌ FAILED</span><br>";
    echo "<pre style='background:#fee;padding:10px;border-radius:6px;'>Error: " . htmlspecialchars($e['message']) . "</pre>";
    exit;
}
echo "<span style='color:green'>✅ Connected</span><br>";

// 3. Test Query (small sample)
echo "<b>3. Test Query on VBAK + KNA1...</b><br>";
$sql = "SELECT t1.kunnr, t2.name1
        FROM sapsr3.VBAK t1
        JOIN sapsr3.KNA1 t2 ON t1.mandt = t2.mandt AND t1.kunnr = t2.kunnr
        WHERE t1.mandt = '400' AND t1.vkorg = '1201'
        GROUP BY t1.kunnr, t2.name1
        ORDER BY t2.name1 ASC";

$stmt = oci_parse($conn, $sql);
if (!oci_execute($stmt)) {
    $e = oci_error($stmt);
    echo "<span style='color:red'>❌ Query Failed:</span><br>";
    echo "<pre style='background:#fee;padding:10px;border-radius:6px;'>" . htmlspecialchars($e['message']) . "</pre>";
    exit;
}

echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse;margin-top:10px;font-family:monospace;'>";
echo "<tr style='background:#1e293b;color:white'><th>KUNNR</th><th>NAME1</th></tr>";
$count = 0;
while ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
    echo "<tr><td>" . htmlspecialchars(trim($row['KUNNR'])) . "</td><td>" . htmlspecialchars(trim($row['NAME1'])) . "</td></tr>";
    $count++;
    if ($count >= 20) break; // Show max 20 rows
}
echo "</table>";
echo "<br><span style='color:green'>✅ Query OK — Found: $count rows (showing max 20)</span>";

oci_free_statement($stmt);
oci_close($conn);
?>
