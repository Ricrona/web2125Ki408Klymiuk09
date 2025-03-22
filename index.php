<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Business Card - Home</title>
</head>
<body>
  <h1>My Business Card</h1>
  <p>Name: Alona Klymiuk</p>
  <p>Position: Student of KI</p>
  <p>Task repo: <a href="https://github.com/Ricrona/web2125Ki408Klymiuk09/tree/main" target="__blank">click here!</a></p>
  <p>Contact: alyona.klymiyk.ki.2021@lpnu.ua</p>

  <?php
  if (isset($_GET['non_ajax_get'])) {
      echo "<p>Non-AJAX GET value: " . htmlspecialchars($_GET['non_ajax_get']) . "</p>";
  }
  if (isset($_POST['non_ajax_post'])) {
      echo "<p>Non-AJAX POST value: " . htmlspecialchars($_POST['non_ajax_post']) . "</p>";
  }
  ?>

  <h2>Non-AJAX Forms</h2>
  <h3>GET Form (Non-AJAX)</h3>
  <form method="GET" action="">
    <label for="non_ajax_get">Enter GET value:</label>
    <input type="text" name="non_ajax_get" id="non_ajax_get">
    <button type="submit">Submit GET</button>
  </form>

  <h3>POST Form (Non-AJAX)</h3>
  <form method="POST" action="">
    <label for="non_ajax_post">Enter POST value:</label>
    <input type="text" name="non_ajax_post" id="non_ajax_post">
    <button type="submit">Submit POST</button>
  </form>

  <hr>

  <h2>AJAX Forms</h2>

  <h3>AJAX POST Form</h3>
  <form id="ajaxPostForm">
    <input type="text" name="data" placeholder="Enter some data">
    <button type="submit">Submit POST</button>
  </form>
  <div id="postResponse"></div>
  
  <h3>AJAX GET Form</h3>
  <form id="ajaxGetForm">
    <input type="text" name="data" placeholder="Enter some data">
    <button type="submit">Submit GET</button>
  </form>
  <div id="getResponse"></div>
  
  <script>
    // AJAX POST
    document.getElementById('ajaxPostForm').addEventListener('submit', function(e) {
      e.preventDefault();
      var formData = new FormData(this);
      fetch('process_post.php', { method: 'POST', body: formData })
        .then(response => response.text())
        .then(data => {
          document.getElementById('postResponse').innerHTML = data;
        })
        .catch(error => console.error('Error:', error));
    });

    // AJAX GET
    document.getElementById('ajaxGetForm').addEventListener('submit', function(e) {
      e.preventDefault();
      var data = new URLSearchParams(new FormData(this));
      fetch('process_get.php?' + data.toString(), { method: 'GET' })
        .then(response => response.text())
        .then(data => {
          document.getElementById('getResponse').innerHTML = data;
        })
        .catch(error => console.error('Error:', error));
    });
  </script>
  
  <footer>
    <p>Page loaded at: <?php echo date("Y-m-d H:i:s"); ?></p>
  </footer>
</body>
</html>
