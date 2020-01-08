<?php
/**
 * @file
 * Contains \DrupalProject\composer\ScriptHandler.
 */
namespace ThunderDevelop\composer;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Script\Event;

use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;

class ScriptHandler {

  public static function downloadDevelopPackages(Event $event) {
    $fs = new Filesystem();

    $io = $event->getIO();
    $composer = $event->getComposer();
    $repositoryManager = $composer->getRepositoryManager();
    $rootPackage = $composer->getPackage();

    $rootExtra = $rootPackage->getExtra();
    $packages = $rootExtra['local-develop-packages'];

    foreach ($packages as $packageString => $packageVersion) {
      $package = $repositoryManager->findPackage($packageString, $packageVersion);
      if ($package) {
        $installPath = self::getInstallPath($package, $composer);

        if (!$fs->exists($installPath)) {
          $repository = $package->getRepository();
          if ($gitDriver = $repository->getDriver()) {
            $gitDriver = $repository->getDriver();
            $repositoryUrl = $gitDriver->getUrl();
            $branchOption = (0 === strpos($packageVersion, 'dev-')) ? '-b ' . substr($packageVersion, strlen('dev-')) . ' ' : '';
            exec('git clone  ' . $branchOption . $repositoryUrl . ' ' . $installPath);
            $io->write('Cloning repository: ' . $packageString);
          }
        }
      }
    }
  }

  /**
   * Reset local repositories to the default branch.
   *
   * @param \Composer\Script\Event $event
   *   The script event.
   */
  public static function resetLocalRepositories(Event $event) {
    $io = $event->getIO();
    $repositoriesInfo = self::getLocalRepositoriesInfo($event);
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $composerRoot = $drupalFinder->getComposerRoot();

    $io->write(PHP_EOL);
    foreach ($repositoriesInfo as $key => $info) {
      $gitCommand = 'git -C ' . $composerRoot . '/' . $info['install_path'];

      $localBranch = trim(shell_exec($gitCommand . ' rev-parse --abbrev-ref HEAD'));

      exec($gitCommand . ' fetch --quiet');
      $gitStatus = shell_exec($gitCommand . ' status --porcelain');
      if (!empty($gitStatus)) {
        $io->write('Stash local changes in ' . $info['package'] . ':' . $localBranch, TRUE);
        exec($gitCommand . ' stash --include-untracked');
      }

      if ($localBranch !== $info['branch']) {
        $io->write('Checkout ' . $info['package'] . ':' . $info['branch'], TRUE);
        exec($gitCommand . ' checkout --quiet ' . $info['branch']);
      }

      $io->write('Merge remote changes into ' . $info['package'] . ':' . $info['branch'], TRUE);
      exec($gitCommand . ' merge --quiet');
    }
  }

  /**
   * Collect information about local repositories.
   *
   * Retrieve available informazion about the repositories defined in the
   * local-develop-packages key of the composer.json.
   *
   * @param \Composer\Script\Event $event
   *   The script event.
   *
   * @return array
   *   The collected repositories.
   */
  protected static function getLocalRepositoriesInfo(Event $event) {
    $repositoriesInfo = [];
    $composer = $event->getComposer();
    $rootExtra = $composer->getPackage()->getExtra();
    $packages = $rootExtra['local-develop-packages'];

    foreach ($packages as $packageString => $packageVersion) {
      $package = $composer->getRepositoryManager()->findPackage($packageString, $packageVersion);
      if (!$package) {
        continue;
      }

      $repository = $package->getRepository();
      if ($gitDriver = $repository->getDriver()) {
        $info = [];
        $info['package'] = $packageString;
        $info['install_path'] = self::getInstallPath($package, $composer);
        $info['url'] = $gitDriver->getUrl();
        $info['branch'] = (0 === strpos($packageVersion, 'dev-')) ? substr($packageVersion, strlen('dev-')) : '';
        $repositoriesInfo[] = $info;
      }
    }
    return $repositoriesInfo;
  }

  /**
   * Return the install path based on package type.
   *
   * @param \Composer\Package\PackageInterface $package
   * @param \Composer\Composer $composer
   *
   * @return bool|string
   */
  protected static function getInstallPath(PackageInterface $package, Composer $composer) {
    $type = $package->getType();

    $prettyName = $package->getPrettyName();
    if (strpos($prettyName, '/') !== false) {
      list($vendor, $name) = explode('/', $prettyName);
    } else {
      $vendor = '';
      $name = $prettyName;
    }

    $availableVars = compact('name', 'vendor', 'type');

    $extra = $package->getExtra();
    if (!empty($extra['installer-name'])) {
      $availableVars['name'] = $extra['installer-name'];
    }

    if ($composer->getPackage()) {
      $extra = $composer->getPackage()->getExtra();
      if (!empty($extra['installer-paths'])) {
        $customPath = self::mapCustomInstallPaths($extra['installer-paths'], $prettyName, $type, $vendor);
        if ($customPath !== false) {
          return self::templatePath($customPath, $availableVars);
        }
      }
    }

    return false;
  }


  /**
   * Search through a passed paths array for a custom install path.
   *
   * @param  array  $paths
   * @param  string $name
   * @param  string $type
   * @param  string $vendor = NULL
   * @return string
   */
  protected static function mapCustomInstallPaths(array $paths, $name, $type, $vendor = NULL) {
    foreach ($paths as $path => $names) {
      if (in_array($name, $names) || in_array('type:' . $type, $names) || in_array('vendor:' . $vendor, $names)) {
        return $path;
      }
    }

    return false;
  }

  /**
   * Replace vars in a path.
   *
   * @param  string $path
   * @param  array  $vars
   * @return string
   */
  protected static function templatePath($path, array $vars = array()) {
    if (strpos($path, '{') !== false) {
      extract($vars);
      preg_match_all('@\{\$([A-Za-z0-9_]*)\}@i', $path, $matches);
      if (!empty($matches[1])) {
        foreach ($matches[1] as $var) {
          $path = str_replace('{$' . $var . '}', $$var, $path);
        }
      }
    }

    return $path;
  }

}
