<?php
namespace Newtron\Core\Error;
/** @var array $error */
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Fatal Error</title>
    <style>
      body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #15171b; }
      .error-container { background: #f8f8f2; color: #15171b; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
      .error-header { color: #d32f2f; border-bottom: 2px solid #d32f2f; padding-bottom: 15px; margin-bottom: 20px; }
      .error-message { font-size: 18px; margin-bottom: 20px; }
      .error-location { background: #f8f8f2; padding: 10px; border-left: 4px solid #d32f2f; }
    </style>
  </head>
  <body>
    <div class="error-container">
      <h1 class="error-header">Fatal Error</h1>
      <div class="error-message"><?= htmlspecialchars($error['message']) ?></div>
      <div class="error-location">
        <strong>File:</strong> <?= htmlspecialchars($error['file']) ?><br>
        <strong>Line:</strong> <?= $error['line'] ?>
      </div>
    </div>
  </body>
</html>
