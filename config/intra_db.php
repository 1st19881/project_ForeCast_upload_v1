<?php
// config/intra_db.php
function getIntraConnection() {
    $SagUser = "web";
    $SagPWD  = "web123";
    $SagDB   = "SAGDB";
    $SagLang = "WE8DEC";

    $conn = @oci_connect($SagUser, $SagPWD, $SagDB, $SagLang);
    if (!$conn) {
        return false;
    }
    return $conn;
}
?>
