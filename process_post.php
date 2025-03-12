<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $data = isset($_POST['data']) ? htmlspecialchars($_POST['data']) : 'No data';
  echo "POST AJAX Response: " . $data . " | Processed at " . date("Y-m-d H:i:s");
}
?>
