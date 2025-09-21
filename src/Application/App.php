<?php
declare(strict_types=1);

namespace Newtron\Core\Application;

class App {
  public function initialize(): void {}

  public function run(): void {}

  public function getVersion(): string {
    return \Composer\InstalledVersions::getRootPackage()['version'];
  }
}
