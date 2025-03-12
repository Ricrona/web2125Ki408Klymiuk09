<?php
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  $data = isset($_GET['data']) ? htmlspecialchars($_GET['data']) : 'No data';
  echo "GET AJAX Response: " . $data . " | Processed at " . date("Y-m-d H:i:s");
}
?>
