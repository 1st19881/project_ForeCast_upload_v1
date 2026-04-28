<?php
// config/database.php
function getDbConnection() {
    $HrmsUser = "hrmsit";
    $HrmsPWD  = "ithrms";
    $HrmsDB   = "HRMS";
    $HrmsLang = "AL32UTF8";

    $conn = @oci_connect($HrmsUser, $HrmsPWD, $HrmsDB, $HrmsLang);
    if (!$conn) {
        return false;
    }
    return $conn;
}
?>
