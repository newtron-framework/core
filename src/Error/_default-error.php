<?php
namespace Newtron\Core\Error;
/** @var int $statusCode */
/** @var string $message */
?>
<!DOCTYPE html>
<html>
  <head>
    <title><?= $statusCode ?> - <?= $message ?></title>
    <style>
      body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f8f8f2; }
      .error-container { display: inline-block; text-align: left; }
      h1 { color: #15171b; }
      p { color: #626784; margin: 20px 0; }
      a { color: #05b6e7; text-decoration: none; }
      a:hover { text-decoration: underline; }
    </style>
  </head>
  <body>
    <div class="error-container">
      <h1><?= $statusCode ?> - <?= $message ?></h1>
      <p>Sorry, something went wrong. Please try again later.</p>
      <p><a href="/">← Back to Home</a></p>
    </div>
  </body>
</html>
