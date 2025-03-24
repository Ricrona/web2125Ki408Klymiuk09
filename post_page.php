<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>POST Page</title>
</head>
<body>
  <h1>POST Request Page</h1>
  <p>This page was loaded with a POST request.</p>
  <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $info = isset($_POST['info']) ? htmlspecialchars($_POST['info']) : 'No info provided';
      echo "<p>Data received: " . $info . "</p>";
    }
  ?>
  
  <footer>
    <p>Page loaded at: <?php echo date("Y-m-d H:i:s"); ?></p>
  </footer>
</body>
</html>
