<?php
namespace Newtron\Core\Error;
/** @var \Throwable $exception */
/** @var array $trace */
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Error - <?= htmlspecialchars(get_class($exception)) ?></title>
    <style>
      body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #15171b; }
      .error-container { background: #f8f8f2; color: #15171b; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
      .error-header { color: #d32f2f; border-bottom: 2px solid #d32f2f; padding-bottom: 15px; margin-bottom: 20px; }
      .error-message { font-size: 18px; margin-bottom: 20px; }
      .error-location { background: #414558; color: #f8f8f2; padding: 10px; border-left: 4px solid #05b6e7; margin-bottom: 20px; }
      .stack-trace { background: #21222c; color: #f8f8f2; padding: 15px; border-radius: 4px; overflow-x: auto; }
      .trace-item { margin-bottom: 10px; }
      .trace-file { color: #05b6e7; }
      .trace-line { color: #fd971f; }
      .trace-function { color: #a6e22e; }
      .request-info { margin-top: 20px; padding: 15px; background: #cdf0fa; border-radius: 4px; }
    </style>
  </head>
  <body>
    <div class="error-container">
      <h1 class="error-header"><?= htmlspecialchars(get_class($exception)) ?></h1>
      
      <div class="error-message">
        <strong><?= htmlspecialchars($exception->getMessage()) ?></strong>
      </div>
      
      <div class="error-location">
        <strong>File:</strong> <?= htmlspecialchars($exception->getFile()) ?><br>
        <strong>Line:</strong> <?= $exception->getLine() ?>
      </div>
      
      <h3>Stack Trace:</h3>
      <div class="stack-trace">
        <?php foreach ($trace as $i => $item): ?>
          <div class="trace-item">
            #<?= $i ?> 
            <?php if (isset($item['file'])): ?>
              <span class="trace-file"><?= htmlspecialchars($item['file']) ?></span>
              <span class="trace-line">(<?= $item['line'] ?>)</span>:
            <?php endif; ?>
            <span class="trace-function">
              <?= isset($item['class']) ? htmlspecialchars($item['class'] . $item['type']) : '' ?>
              <?= htmlspecialchars($item['function']) ?>()
            </span>
          </div>
        <?php endforeach; ?>
      </div>
      
      <div class="request-info">
        <h3>Request Information:</h3>
        <strong>URL:</strong> <?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A') ?><br>
        <strong>Method:</strong> <?= htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'N/A') ?><br>
      </div>
    </div>
  </body>
</html>
